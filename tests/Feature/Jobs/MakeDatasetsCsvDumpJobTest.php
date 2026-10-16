<?php

namespace Tests\Feature\Jobs;

use App\Jobs\MakeDatasetsCsvDumpJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The dump is tested with selected datasets only (see dump()): by default the job writes every current dataset.
 */
class MakeDatasetsCsvDumpJobTest extends JobTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // the job writes to storage/app/kerndaten_dumps and stores the path of the dump in the cache,
        // which /downloads reads: use a temporary directory and an array cache
        $this->useTemporaryPaths();
        config(['cache.default' => 'array']);
    }

    public function testWritesDatasetAsCsvRow()
    {
        $this->createCpv('99100000', 'Testbranche Straßenbau');
        $this->createCpv('99110000', 'Testbranche Asphalt');
        DB::table('procedures')->insertOrIgnore(['code' => 'PT_OPEN', 'name' => 'offenes Verfahren']);

        $id = $this->createDataset([
            'title'         => 'Sanierung der Ortsdurchfahrt',
            'description'   => "Erneuerung der Fahrbahn\nund der Gehsteige",
            'cpv_code'      => '99100000',
            'contract_type' => 'WORKS',
            'val_total'     => 123456789,
            'date_start'    => '2026-04-01',
            'threshold'     => 1,
            'rd_notification' => 1,
        ], $this->createOrganization(['name' => 'Testgemeinde Musterdorf']));
        DB::table('cpv_dataset')->insert(['cpv_code' => '99110000', 'dataset_id' => $id, 'main' => 0]);
        DB::table('dataset_procedure')->insert(['procedure_code' => 'PT_OPEN', 'dataset_id' => $id]);
        $this->addOfferor($id, $this->createOrganization(['name' => 'Testverband Musterregion']), true);
        $this->addContractor($id, $this->createOrganization(['name' => 'Muster Bau GmbH']));
        $this->addContractor($id, $this->createOrganization(['name' => 'Beispiel Asphalt KG']), true);
        $this->addContractor($id, $this->createOrganization(['name' => 'Asphalt Beispiel OG']), true);

        $rows = $this->dump([$id]);

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame((string) $id, $row['id']);
        $this->assertSame(self::DATASET_TYPE, $row['art']);
        $this->assertSame('01.01.2037', $row['aktualisiert']);
        $this->assertSame('Bauauftrag', $row['auftragsart']);
        $this->assertSame('99100000 Testbranche Straßenbau', $row['cpv']);
        $this->assertSame('99110000 Testbranche Asphalt', $row['cpv zusätzlich']);
        $this->assertSame('Sanierung der Ortsdurchfahrt', $row['titel']);
        $this->assertSame('Testgemeinde Musterdorf', $row['auftraggeber']);
        $this->assertSame('Testverband Musterregion', $row['weitere auftraggeber']);
        $this->assertSame('Muster Bau GmbH', $row['lieferant']);
        // several organizations in one cell are separated by an empty line
        $this->assertSame("Beispiel Asphalt KG\n\nAsphalt Beispiel OG", $row['weitere lieferanten']);
        $this->assertSame('offenes Verfahren', $row['verfahrensart']);
        // in euros
        $this->assertSame('1234567.89', $row['wert']);
        $this->assertSame("Erneuerung der Fahrbahn\nund der Gehsteige", $row['beschreibung']);
        $this->assertSame('01.04.2026', $row['geplanter ausführungsbeginn']);
        $this->assertSame('OSB', $row['oberschwellenbereich / unterschwellenbereich']);
        $this->assertSame('ja', $row['forschung und entwicklung']);
    }

    /**
     * Additional organizations are separated by an empty line, their identifiers are on lines of their own.
     */
    public function testWritesIdentifiersOfAdditionalOrganizations()
    {
        $id = $this->createDataset();
        $this->addOfferor($id, $this->createOrganization(['name' => 'Testverband Musterregion']), true);
        $this->addContractor($id, $this->createOrganization(['name' => 'Muster Bau GmbH']));
        $this->addContractor($id, $this->createOrganization(['name' => 'Beispiel Asphalt KG']), true);
        DB::table('offerors')->where('dataset_id', $id)->where('is_extra', 1)
            ->update(['national_id' => 'FN999998z', 'reference_number' => 'MR-2026-7']);
        DB::table('contractors')->where('dataset_id', $id)->where('is_extra', 1)
            ->update(['national_id' => '9999999999999']);

        $row = $this->dump([$id])[0];

        $this->assertSame("Testverband Musterregion\nStammzahl: FN999998z\nGeschäftszahl: MR-2026-7", $row['weitere auftraggeber']);
        $this->assertSame("Beispiel Asphalt KG\nStammzahl: 9999999999999", $row['weitere lieferanten']);
    }

    /**
     * Without matching datasets the dump only has the header row, but is still stored.
     */
    public function testDumpWithoutDatasetsHasOnlyHeader()
    {
        $this->assertSame([], $this->dump([0]));
        $this->assertFileExists(MakeDatasetsCsvDumpJob::getCurrentFilePath('absolute'));
    }

    /**
     * Selected ids are refused as "not yet fit for production": such a dump would replace the current download.
     */
    public function testConstructorRefusesIds()
    {
        $this->expectException(\InvalidArgumentException::class);

        new MakeDatasetsCsvDumpJob([1, 2]);
    }

    public function testSkipsOlderVersionsAndDisabledDatasets()
    {
        $current  = $this->createDataset();
        $older    = $this->createDataset(['is_current_version' => 0]);
        $disabled = $this->createDataset(['disabled_at' => now()]);

        $rows = $this->dump([$current, $older, $disabled]);

        $this->assertSame([(string) $current], array_column($rows, 'id'));
    }

    /**
     * The zip file is stored in storage/app/kerndaten_dumps and registered in the cache for /downloads.
     */
    public function testRegistersDumpAsCurrentDownload()
    {
        $this->dump([$this->createDataset()]);

        $zipPath = MakeDatasetsCsvDumpJob::getCurrentFilePath('absolute');
        $this->assertFileExists($zipPath);
        $this->assertSame(storage_path('app/kerndaten_dumps/'.basename($zipPath)), $zipPath);
        $this->assertStringStartsWith(MakeDatasetsCsvDumpJob::CSV_FILE_PREFIX.'_', basename($zipPath));
        $this->assertSame('kerndaten_dumps/'.basename($zipPath), MakeDatasetsCsvDumpJob::getCurrentFilePath());
        $this->assertNotNull(MakeDatasetsCsvDumpJob::getCurrentFileTimestamp());
        // the temporary directory with the csv file is removed
        $this->assertSame([basename($zipPath)], $this->filesIn(storage_path('app/kerndaten_dumps')));
    }

    /**
     * Dumps of the last 30 days are kept. Of older dumps only the first one of each month is kept.
     */
    public function testCleanUpThinsOutOldDumps()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 12:00:00'));
        $dir = storage_path('app/kerndaten_dumps');
        mkdir($dir);
        // file => kept
        $files = [
            'kerndaten_csv_dump_20260701_0400.zip' => true,
            'kerndaten_csv_dump_20260731_0400.zip' => false,
            'kerndaten_csv_dump_20260801_0400.zip' => true,
            'kerndaten_csv_dump_20260811_0400.zip' => false,
            'kerndaten_csv_dump_20260813_0400.zip' => true,   // not older than 30 days
            'kerndaten_csv_dump_20260910_0400.zip' => true,
            'anderer_dump_20260702_0400.zip'       => true,   // not a dump of the job
        ];
        foreach (array_keys($files) as $file) {
            touch($dir.'/'.$file);
        }

        $job = new class extends MakeDatasetsCsvDumpJob {
            public function runCleanUp()
            {
                $this->cleanUp();
            }
        };
        $job->runCleanUp();

        $kept = array_keys(array_filter($files));
        sort($kept);
        $this->assertSame($kept, $this->filesIn($dir));
    }

    /**
     * Runs the job for the given datasets and returns the rows of the csv file, keyed by the column headers.
     */
    private function dump(array $ids)
    {
        $job = new class($ids) extends MakeDatasetsCsvDumpJob {
            public function __construct(array $ids)
            {
                parent::__construct();

                // the constructor refuses ids ("not yet fit for production"), but the dump itself supports them
                $this->parameters['ids'] = $ids;
            }
        };
        $job->handle();

        $zip = new \ZipArchive();
        $zip->open(cache(MakeDatasetsCsvDumpJob::CACHE_CURRENT_PATH_ABSOLUTE));
        $csv = $zip->getFromIndex(0);
        $zip->close();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle);
        $rows   = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($header, $row);
        }
        fclose($handle);

        return $rows;
    }

    private function filesIn($dir)
    {
        $files = array_values(array_diff(scandir($dir), ['.', '..']));
        sort($files);

        return $files;
    }
}
