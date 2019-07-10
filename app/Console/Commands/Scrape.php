<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Scrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kick Off Scrape Job';

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
        $this->info('Starting Scrape Job');
        Log::channel('scraper_daily')->info('Starting Scrape Job');

        $job = app()->make('App\Jobs\Scrape');
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished Scrape Job in '.$runtime.' seconds');
        Log::channel('scraper_daily')->info('Finished Scrape Job in '.$runtime.' seconds');
    }
}
