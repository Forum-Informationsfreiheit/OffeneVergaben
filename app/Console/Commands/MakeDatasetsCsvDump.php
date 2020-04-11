<?php

namespace App\Console\Commands;

use App\Jobs\MakeDatasetsCsvDumpJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MakeDatasetsCsvDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:dump-datasets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For Testing purposes only. Trigger the Job that creates the zipped CSV Datasets (daily) dump.';

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
        $this->info('Starting Datasets CSV Dump Job');
        Log::info('Starting Datasets CSV Dump Job');

        $params = [ ];

        $job = app()->make('App\Jobs\MakeDatasetsCsvDumpJob',$params);
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished Datasets CSV Dump Job in '.$runtime.' seconds');
        Log::info('Finished Datasets CSV Dump Job in '.$runtime.' seconds');
    }
}
