<?php

namespace App\Jobs;

use App\DOMKerndaten;
use App\ScraperKerndaten;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class Process
{
    use Dispatchable;

    protected $timestamp;

    protected $recordIds;

    protected $log;

    /**
     * Constructs the new processing Job, handle method will be fired on dispatch.
     *
     * @param array $ids - an array of kerndaten ids as referencing the id column in the scraper database table 'kerndaten'
     */
    public function __construct($ids)
    {
        $this->recordIds = $ids;

        // use same timestamp for all requests
        $this->timestamp = Carbon::now();

        // setup explicit processor log
        $this->log = Log::channel('processor_daily');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dump(             'Process ' . count($this->recordIds)." kerndaten");
        $this->log->debug('Process ' . count($this->recordIds)." kerndaten");

        // Use "block sized" processing to prevent any kind of memory issues
        $blockSize = 100;
        $index = 0;

        $records = $this->getRawKerndatenRecords(0, $blockSize);

        $processedCount = 0;

        while(count($records) > 0) {
            dump(             "Process block info: index:".($index+1).", length:".(count($records)));
            $this->log->debug("Process block info: index:".($index+1).", length:".(count($records)));

            foreach($records as $record) {
                $success = false;

                echo ("load DOM model for kerndaten record ".$record->id . "\n");
                $dom = new DOMKerndaten($record->xml,null,[ 'record_id' => $record->id ]);

                // actually do some processing
                // TODO processing
                // $data = $this->preProcessor->getData();

                /*
                if ($data) {
                    $success = $this->process($record,$data);
                } else {
                    dump('Failed preprocessing for record:'.$record->id);
                    $this->log->error('Failed preprocessing for record:'.$record->id);
                }
                */

                $processedCount += $success ? 1 : 0;
            }

            dump('********************** NEXT BLOCK ********************');

            // prepare for next iteration
            $index += count($records);
            $records = $this->getRawKerndatenRecords($index, $blockSize);
        }

        dump(             "Processed $processedCount records.");
        $this->log->debug("Processed $processedCount records.");

        // finally do some housekeeping
        // update the datasource table with info of the last processed version
        if ($processedCount > 0) {
            dump(             "Finalizing process job...");
            $this->log->debug("Finalizing process job...");

            //$this->updateVersionInfo(); TODO
        }
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array of Kerndaten Models
     */
    protected function getRawKerndatenRecords($offset = null, $limit = null) {
        $ids = array_slice($this->recordIds,$offset,$limit);

        $data = ScraperKerndaten::whereIn('id',$ids)->get();

        return $data;
    }
}
