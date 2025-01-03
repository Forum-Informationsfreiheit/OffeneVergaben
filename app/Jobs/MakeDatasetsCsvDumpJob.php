<?php

namespace App\Jobs;

use App\Dataset;
use App\Http\Controllers\DownloadController;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MakeDatasetsCsvDumpJob implements ShouldQueue
{
    use Dispatchable;

    /**
     * This is a storage path, use in combination with
     * storage_path(<const>) to get the absolute path.
     *
     * The relative path from the root directory can be build with
     * ./storage/app/<const>
     */
    const STORAGE_OUTPUT_DIRECTORY = "kerndaten_dumps";

    /**
     * Cache entries used to remember 'forever' the most current file path
     * for a data dump. This is to avoid scanning the directory for the correct file.
     */
    const CACHE_CURRENT_PATH_ABSOLUTE = "kerndaten_dump_current_path_absolute";
    const CACHE_CURRENT_PATH_STORAGE  = "kerndaten_dump_current_path_storage";

    /**
     * The timestamp of the creation of the latest file. So one can have an easy way
     * to retrieve the datetime without having to look at the actual file modification date
     * or having to parse the file name.
     */
    const CACHE_CURRENT_FILE_TIMESTAMP = "kerndaten_dump_current_file_timestamp";

    const CSV_FILE_PREFIX = "kerndaten_csv_dump";

    const CSV_DELIMITER = ',';

    protected $parameters = [
        'ids' => null,
    ];

    /**
     * @param null|array $ids - DONT USE THIS RIGHT NOW!
     *                          Job can be called with a given set of dataset ids
     *                          use with caution as this will override the cache entries.
     *                          (Can be a useful feature but some more programming needs to be done)
     */
    public function __construct($ids = null)
    {
        if ($ids != null) {
            throw new \InvalidArgumentException('This feature is not yet fit for production.');
        }

        if ($ids && is_array($ids)) {
            $this->parameters['ids'] = $ids;
        }
    }

    /**
     * Get the current file
     *
     * @param $pathType
     *
     * @return string
     */
    public static function getCurrentFilePath($pathType = 'storage') {
        if ($pathType == 'absolute') {
            return cache()->get(self::CACHE_CURRENT_PATH_ABSOLUTE);
        } else {
            return cache()->get(self::CACHE_CURRENT_PATH_STORAGE);
        }
    }

    public static function getCurrentFileTimestamp() {
        return cache()->get(self::CACHE_CURRENT_FILE_TIMESTAMP);
    }


    /**
     * IMPORTANT: CALL THIS FUNCTION IN A LOOP AND MODIFY OFFSET/BLOCKSIZE ACCORDINGLY
     *            OR YOU WILL END IN A ENDLESS LOOP.
     *
     * @param int $offset
     * @param int $blockSize
     * @return null
     */
    protected function datasetsStream($offset = 0, $blockSize = 250) {
        $query = Dataset::current()->with('offerorsAdditional')->with('contractorsAdditional')->with('cpvsAdditional')->with('procedures');

        if ($this->parameters['ids']) {
            $query->whereIn('id',$this->parameters['ids']);
        }

        $query->offset($offset)->limit($blockSize);

        $records = $query->get();

        return $records->count() > 0 ? $records : null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->parameters['ids']) {
            dump('MakeDatasetsCsvDumpJob called with predefined ids. Count='.count($this->parameters['ids']));
            Log::info('MakeDatasetsCsvDumpJob called with predefined ids. Count='.count($this->parameters['ids']));
        } else {
            $count = Dataset::current()->count();
            dump('MakeDatasetsCsvDumpJob called with no ids --> dump all current datasets count='.$count);
            Log::info('MakeDatasetsCsvDumpJob called with no ids --> dump all current datasets count='.$count);
        }

        $this->make();

        // 2024-12-13: added new clean up function
        $this->cleanUp();
    }

    /**
     * Create the CSV, use a blocked approach to not overload our precious memory
     */
    protected function make() {
        $idx = 0;

        $rootDir = storage_path('app' . DIRECTORY_SEPARATOR .self::STORAGE_OUTPUT_DIRECTORY);
        if (!file_exists($rootDir)) {
            mkdir($rootDir);
        }

        // use a temporary directory (clean up afterwards) for creating and compressing the file
        $tempDir = $rootDir . DIRECTORY_SEPARATOR . 'temp_' . Carbon::now()->format('Ymd_Hiu');
        mkdir($tempDir);

        $csvPath = $tempDir . DIRECTORY_SEPARATOR . self::CSV_FILE_PREFIX .'_' . Carbon::now()->format('Ymd_Hi') . '.csv';
        $csvName = basename($csvPath);

        $fHandle = fopen($csvPath, 'w');

        $this->writeHeaderToCsv($fHandle);
        while($datasets = $this->datasetsStream($idx)) {
            //dump('index '.$idx);

            foreach($datasets as $dataset) {
                $this->writeDatasetToCsv($fHandle, $dataset);

                $idx++;
            }
        }

        fclose($fHandle);

        // success ?
        if ($idx === 0) {
            // oops
            dump('No datasets written to csv');
            Log::error('No datasets written to csv');
        }

        // zip it
        $tempZipPath = str_replace('.csv','.zip',$csvPath);
        $zipName = basename($tempZipPath);
        $zip = new \ZipArchive();
        $zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($csvPath, $csvName);
        $zip->close();

        // move it
        $zipPath = $rootDir . DIRECTORY_SEPARATOR . $zipName;
        // (use php rename() to do it instead of Storage::move as Storage wants to operate in relative paths)
        rename($tempZipPath, $zipPath);

        // delete it (just kidding, only the temp directory)
        unlink($csvPath);
        rmdir($tempDir);

        // finalize
        if (file_exists($zipPath)) {
            // Write/Overwrite paths in cache (forever means use cache as a DB equivalent)
            cache()->forever(self::CACHE_CURRENT_PATH_ABSOLUTE,$zipPath);
            cache()->forever(self::CACHE_CURRENT_PATH_STORAGE, self::STORAGE_OUTPUT_DIRECTORY . DIRECTORY_SEPARATOR . basename($zipPath));
            cache()->forever(self::CACHE_CURRENT_FILE_TIMESTAMP,Carbon::now()->toDateTimeString());

            // todo log file size
            Log::info('Datasets csv dump created successfully:'. $zipName);
            dump('Datasets csv dump created successfully:'. $zipName);
        }
    }

    protected function writeHeaderToCsv($file) {
        fputcsv($file, [
            'id', 'art', 'aktualisiert', 'auftragsart', 'cpv', 'cpv zusätzlich','nuts_code', 'titel',

            'auftraggeber',
            'auftraggeber stammzahl',
            'auftraggeber geschäftszahl',
            'weitere auftraggeber',

            'lieferant',
            'lieferant stammzahl',
            'weitere lieferanten',

            'verfahrensart',
            'wert',
            'beschreibung',

            'geplanter ausführungsbeginn',
            'endzeitpunkt / erfüllungszeitpunkt',
            'laufzeit (in tagen)',
            'schlusstermin für den eingang',
            'tag vertragsabschluss',
            'tag erstmalige verfügbarkeit',
            'letzte änderung der ausschreibung',
            'ende der stillhaltefrist bei Widerrufsentscheidung',

            'anzahl teilnehmer',
            'anzahl teilnehmer kmu',
            'anzahl eingegangener angebote',
            'anzahl eingegangener angebote (kmu)',
            'angabe/anzahl kmu der auftragnehmer',

            'wert vor veränderung',
            'wert zusätzliche leistung',
            'oberschwellenbereich / unterschwellenbereich',

            'gründe notwendigkeit',
            'beschreibung der maßgeblichen gründe',
            'forschung und entwicklung',
        ], self::CSV_DELIMITER);
    }

    protected function writeDatasetToCsv($file, $dataset) {
        // write one row
        fputcsv($file, [
            $dataset->id,
            $dataset->type_code,
            $dataset->item_lastmod->format('d.m.Y'),
            $dataset->contract_type ? __('dataset.contract_types.'.$dataset->contract_type) : (
                $dataset->ocm_contract_type ? __('dataset.ocm_contract_types.'.$dataset->ocm_contract_type) : null
            ),
            $dataset->cpv_code ? $dataset->cpv_code . ' ' . $dataset->cpv->name : null,
            $this->cpvsAdditionalToCell($dataset),
            $dataset->nuts_code,
            $dataset->title,

            $dataset->offeror->name,
            $dataset->offeror->national_id,
            $dataset->offeror->reference_number,
            $this->offerorsAdditionalToCell($dataset),

            $dataset->contractor ? $dataset->contractor->name : null,
            $dataset->contractor ? $dataset->contractor->national_id : null,
            $this->contractorsAdditionalToCell($dataset),

            $dataset->procedures ? procedure_label($dataset->procedures->pluck('code')->toArray()) : null,
            $dataset->val_total ? $dataset->val_total / 100 : null,
            $dataset->description,

            $dataset->date_start ? $dataset->date_start->format('d.m.Y') : null,
            $dataset->date_end ? $dataset->date_end->format('d.m.Y') : null,
            $dataset->duration,
            $dataset->datetime_receipt_tenders ? $dataset->datetime_receipt_tenders->format('d.m.Y') : null,
            $dataset->date_conclusion_contract ? $dataset->date_conclusion_contract->format('d.m.Y') : null,
            $dataset->date_first_publication ? $dataset->date_first_publication->format('d.m.Y') : null,
            $dataset->datetime_last_change ? $dataset->datetime_last_change->format('d.m.Y') : null,
            $dataset->deadline_standstill ? $dataset->deadline_standstill->format('d.m.Y') : null,

            $dataset->nb_participants,
            $dataset->nb_participants_sme,
            $dataset->nb_tenders_received,
            $dataset->nb_sme_tender,
            $dataset->nb_sme_contractor,

            $dataset->val_total_before ? $dataset->val_total_before / 100 : null,
            $dataset->val_total_after ? $dataset->val_total_after / 100 : null,
            $dataset->threshold !== null ? ($dataset->threshold === true ? 'OSB' : 'USB') : null,

            $dataset->info_modifications,
            $dataset->justification,
            $dataset->rd_notification ? 'ja' : null,

        ], self::CSV_DELIMITER);
    }

    /**
     * Verwende doppelten Linebreak \n\n zwischen verschiedenen Auftraggebern
     * Verwende einfachen Linebreak \n   zwischen verschiedenen Werten eines Auftraggebers
     *
     * @param $dataset
     * @return string
     */
    protected function offerorsAdditionalToCell($dataset) {
        $result = [];

        foreach($dataset->offerorsAdditional as $offeror) {
            $str = [];

            $str[] = $offeror->name;
            if ($offeror->national_id) {
                $str[] = "Stammzahl: " . $offeror->national_id;
            }
            if ($offeror->reference_number) {
                $str[] = "Geschäftszahl: " . $offeror->reference_number;
            }
            // Telefon, Kontakt, Email erstmal weglassen im CSV Export

            $result[] = join("\n",$str);
        }

        return join("\n\n", $result);
    }

    /**
     * Same logic as with offerors, but less values.
     *
     * @param $dataset
     * @return string
     */
    protected function contractorsAdditionalToCell($dataset) {
        $result = [];

        foreach($dataset->contractorsAdditional as $contractor) {
            $str = [];

            $str[] = $contractor->name;

            if ($contractor->national_id) {
                $str[] = "Stammzahl: " . $contractor->national_id;
            }

            $result[] = join("\n",$str);
        }

        return join("\n\n", $result);
    }

    protected function cpvsAdditionalToCell($dataset) {
        $result = [];

        foreach($dataset->cpvsAdditional as $cpv) {
            $result[] = $cpv->code . ' ' . $cpv->name;
        }

        return join("\n",$result);
    }

    /**
     * keep one file per month, but only remove files older than 30 days (to avoid edge cases)
     */
    protected function cleanUp() {

        $dateMap = collect([]);
        $threshold = Carbon::now()->subDays(30);

        // 1. read all files in output directory
        $dir = storage_path('app' . DIRECTORY_SEPARATOR .self::STORAGE_OUTPUT_DIRECTORY);

        if (!file_exists($dir)) {
            return;
        }

        // NOTE: the sorted order is alphabetical in ascending order
        $files = scandir($dir);

        // 2. iterate, check date in filename
        foreach($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            // $file contains the filename inside the given directory and not a path!
            $filePathAbs = $dir . '/' . $file;
            $fileName = basename($filePathAbs);

            // sanity checks...
            // only operate on files that match the expected file prefix
            if (strpos($fileName,self::CSV_FILE_PREFIX) !== 0) {
                continue;
            }
            // only operate on .zip files
            if (count(explode(".",$fileName)) !== 2 || explode(".",$fileName)[1] !== "zip") {
                continue;
            }
            // parse date from filename (expecting Ymd format)
            $parsedDate = substr($fileName,
                strlen(self::CSV_FILE_PREFIX) + 1, // +1 for following _
                8 // e.g. 20200115
            );
            // last sanity check
            if (!is_numeric($parsedDate)) {
                continue;
            }

            $dateOfFile = Carbon::createFromFormat("Ymd",$parsedDate);

            // age check (always keep the most recent files)
            if (!$dateOfFile->isBefore($threshold)) {
                continue;
            }

            // keep first file of month, remove the rest
            // how? iterating over list of files in asc order so file x_20200101 will always come before x_20200102 will come before x_20200103 etc.
            //      so file x_20200101 will claim 202001 and not be removed but every other file with the same key will
            $dateKey = $dateOfFile->format("Ym"); // monthly key

            if (!$dateMap->has($dateKey)) {
                $dateMap->put($dateKey,$fileName);
                continue; // KEEP the file that claims the datekey
            }

            // REMOVE the rest
            // at this point the key of the file is already claimed and the file is older than 30 days --> remove!
            Log::info('Clean up old kerndaten_dump: remove '. $fileName);
            unlink($filePathAbs);
        }
    }
}
