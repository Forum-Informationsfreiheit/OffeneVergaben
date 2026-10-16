<?php

namespace App\Console\Commands;

use App\Models\ScraperKerndaten;
use App\OrganizationMatcher;
use App\RecordParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Psr\Log\NullLogger;

/**
 * Updates existing datasets in place from the xml in the scraper database, e.g. after the parser was improved.
 *
 * Unlike fif:process, this creates no new dataset version: the datasets keep their ids (and urls), and subscribers
 * are not notified again. Only the selected parts are changed:
 *
 * --further-information  datasets.further_information (eForms only)
 * --offerors             the contracting bodies (offerors): national id, contact data, reference number and the
 *                        organization they are linked to
 */
class UpdateFromXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:update-from-xml
        {--further-information : re-create the further information (eForms only)}
        {--offerors : re-derive the contracting bodies and their organizations}
        {--format=eforms : datasets of this source format: eforms, kerndaten or all}
        {--dataset_id=* : only these datasets}
        {--dry-run : only show what would change}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing datasets in place from their xml (no new version).';

    // offeror columns updated from the xml (without organization_id)
    const OFFEROR_COLUMNS = ['national_id', 'name', 'phone', 'email', 'contact', 'domain', 'reference_number'];

    /**
     * @var OrganizationMatcher
     */
    protected $matcher;

    protected $dryRun;

    // ids of organizations whose datasets changed (for fif:update-organization-stats)
    protected $affectedOrganizations = [];

    protected $counts = ['datasets' => 0, 'further_information' => 0, 'offerors' => 0, 'skipped' => 0, 'failed' => 0];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->option('further-information') && !$this->option('offerors')) {
            $this->error('Nothing to do: use --further-information and/or --offerors.');
            return 1;
        }

        $this->dryRun = (bool)$this->option('dry-run');
        $this->matcher = new OrganizationMatcher();

        $query = DB::table('datasets')->whereNotNull('scraper_kerndaten_id');

        if ($this->option('format') !== 'all') {
            $query->where('source_format', $this->option('format'));
        }
        if ($this->option('dataset_id')) {
            $query->whereIn('id', $this->option('dataset_id'));
        }

        $query->select(['id', 'scraper_kerndaten_id', 'source_format'])->orderBy('id')->chunk(200, function ($datasets) {
            $xmls = ScraperKerndaten::whereIn('id', $datasets->pluck('scraper_kerndaten_id'))->pluck('xml', 'id');

            foreach ($datasets as $dataset) {
                $this->counts['datasets']++;

                try {
                    if (!isset($xmls[$dataset->scraper_kerndaten_id])) {
                        throw new \RuntimeException("kerndaten record {$dataset->scraper_kerndaten_id} not found");
                    }

                    // the unparsed elements were logged when the record was processed
                    $data = RecordParser::parse($xmls[$dataset->scraper_kerndaten_id], new NullLogger(), $dataset->scraper_kerndaten_id);
                    if (!$data) {
                        throw new \RuntimeException('unknown format');
                    }

                    DB::transaction(function () use ($dataset, $data) {
                        if ($this->option('further-information') && $dataset->source_format === 'eforms') {
                            $this->updateFurtherInformation($dataset, $data);
                        }
                        if ($this->option('offerors')) {
                            $this->updateOfferors($dataset, $data);
                        }
                    });
                } catch (\Throwable $ex) {
                    $this->error("Dataset {$dataset->id}: " . $ex->getMessage());
                    $this->counts['failed']++;
                }
            }
        });

        $this->report();

        return $this->counts['failed'] ? 1 : 0;
    }

    protected function updateFurtherInformation($dataset, $data) {
        if (!$this->dryRun) {
            // query builder: keeps updated_at, which tells when the dataset was processed
            DB::table('datasets')->where('id', $dataset->id)->update([
                'further_information' => json_encode($data->furtherInformation, JSON_UNESCAPED_UNICODE),
            ]);
        }

        $this->counts['further_information']++;
    }

    /**
     * The offerors of the dataset in the order Process created them: main offeror, then the additional ones.
     * Datasets whose number of contracting bodies changed are skipped, as offerors can't be matched then.
     */
    protected function updateOfferors($dataset, $data) {
        $offerors = DB::table('offerors')->where('dataset_id', $dataset->id)->orderBy('is_extra')->orderBy('id')->get();
        $addresses = array_merge([$data->contractingBody->address], $data->contractingBody->addressAdditional);

        if (count($offerors) !== count($addresses)) {
            $this->warn("Dataset {$dataset->id}: " . count($offerors) . ' offerors stored, ' . count($addresses) . ' in the xml, skipped.');
            $this->counts['skipped']++;
            return;
        }

        foreach ($offerors as $i => $offeror) {
            $address = $addresses[$i];

            // as Process: the reference number of the main offeror is in the object contract
            $new = [
                'national_id'      => $address->nationalId,
                'name'             => $address->officialName,
                'phone'            => $address->phone,
                'email'            => $address->email,
                'contact'          => $address->contact,
                'domain'           => $address->domain,
                'reference_number' => $i === 0 ? $data->objectContract->refNumber : $address->refNumber,
            ];

            $changes = [];
            foreach (self::OFFEROR_COLUMNS as $column) {
                if ((string)$offeror->$column !== (string)$new[$column]) {
                    $changes[$column] = [$offeror->$column, $new[$column]];
                }
            }

            // a dry run must not create organizations: then only an existing match is shown, else "new"
            $organization = $this->dryRun ?
                $this->matcher->find($new['national_id'], $new['name']) :
                $this->matcher->matchOrCreate($new['national_id'], $new['name']);
            $organizationId = $organization ? $organization->id : null;

            if ((int)$organizationId !== (int)$offeror->organization_id) {
                $changes['organization_id'] = [$offeror->organization_id, $organizationId];
                $this->affectedOrganizations[$offeror->organization_id] = true;
                if ($organizationId) {
                    $this->affectedOrganizations[$organizationId] = true;
                }
            }

            if (!$changes) {
                continue;
            }

            $this->line("Dataset {$dataset->id}, offeror {$offeror->id} ({$offeror->name}): " . $this->describe($changes));
            $this->counts['offerors']++;

            if (!$this->dryRun) {
                DB::table('offerors')->where('id', $offeror->id)->update(array_merge($new, ['organization_id' => $organizationId]));
            }
        }
    }

    protected function describe(array $changes) {
        $parts = [];

        foreach ($changes as $column => [$old, $new]) {
            if ($column === 'organization_id') {
                $parts[] = 'organization #' . ($old ?: '-') . ' → ' . ($new ? '#' . $new : 'new');
            } else {
                $parts[] = $column . ' ' . var_export($old, true) . ' → ' . var_export($new, true);
            }
        }

        return implode(', ', $parts);
    }

    protected function report() {
        $verb = $this->dryRun ? 'Would update' : 'Updated';

        $this->info(sprintf('%d datasets checked. %s: further information of %d datasets, %d offerors. %d skipped, %d failed.',
            $this->counts['datasets'], $verb, $this->counts['further_information'], $this->counts['offerors'],
            $this->counts['skipped'], $this->counts['failed']));

        $organizations = array_keys(array_filter($this->affectedOrganizations));
        if ($organizations && !$this->dryRun) {
            // the counts of datasets per organization are kept in the organizations table
            $this->warn('Organization links changed, update their statistics: php artisan fif:update-organization-stats --organization=<id> for '
                . implode(', ', $organizations));
        }
    }
}
