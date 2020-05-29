<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CleanUpStaleTemporaryDownloadLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:clean-up-tmp-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command deletes old system links from the temporary public/tmp directory.';

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
        $this->info('Starting Clean Up job for temporary system links.');
        Log::info('Starting Clean Up job for temporary system links.');

        $params = [ ];

        $job = app()->make('App\Jobs\CleanUpStaleTemporaryDownloadLinksJob',$params);
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished Clean Up job for temporary system links in '.$runtime.' seconds');
        Log::info('Finished Clean Up job for temporary system links in '.$runtime.' seconds');
    }
}
