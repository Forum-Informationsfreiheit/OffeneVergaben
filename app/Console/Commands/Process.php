<?php

namespace App\Console\Commands;

use App\ScraperKerndaten;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Process extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:process {--kerndaten_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kick Off Processing Job';

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
        $this->info('Starting Process Job');
        Log::channel('processor_daily')->info('Starting Process Job');

        $kerndatenIds = $this->getKerndatenToBeProcessed();

        $params = [ 'ids' => $kerndatenIds ];

        $job = app()->make('App\Jobs\Process',$params);
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished Process Job in '.$runtime.' seconds');
        Log::channel('processor_daily')->info('Finished Process Job in '.$runtime.' seconds');
    }

    protected function getKerndatenToBeProcessed() {
        // 1. check console parameter
        // single or multiple ids seperated by comma
        $kerndatenId = $this->option('kerndaten_id');

        if ($kerndatenId) {
            return array_map('trim',explode(',',$kerndatenId));
        }

        // 2. default: everything that has not been processed
        return ScraperKerndaten::unprocessed()->pluck('id')->toArray();
    }
}
