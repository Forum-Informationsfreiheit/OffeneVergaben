<?php

namespace App\Console\Commands;

use App\Dataset;
use App\DataSourcePreProcessor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DumpPreProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:pre-process-dump {datasetId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a debug command. Freshly preprocess a single dataset (use dataset id as argument) from xml source and print it to the console. ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start = Carbon::now();
        $this->info('Starting Dump-Pre-Process Job');

        $datasetId = $this->argument('datasetId');

        // load the xml from datasetId
        $dataset = Dataset::find($datasetId);

        if (!$dataset) {
            $this->warn('Dataset '.$datasetId.' not found.');
        }

        // dont write this debug pre processing to the regular processing log
        $preProcessor = new DataSourcePreProcessor('dev');

        $preProcessor->preProcess($dataset->xml);

        dump($preProcessor->getData());

        $runtime = $start->diffInSeconds(Carbon::now());
        $this->info('Finished Dump-Pre-Process Job in '.$runtime.' seconds');
    }
}
