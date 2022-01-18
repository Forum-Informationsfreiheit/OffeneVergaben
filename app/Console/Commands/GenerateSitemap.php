<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:generate-sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap(s). Supposed to run daily.';

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
        $this->info('Starting to Generate Sitemap.');
        Log::info('Starting to Generate Sitemap.');

        $params = [ ];

        $job = app()->make('App\Jobs\GenerateSitemapJob',$params);
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished sitemaps in '.$runtime.' seconds');
        Log::info('Finished sitemaps in '.$runtime.' seconds');
    }
}
