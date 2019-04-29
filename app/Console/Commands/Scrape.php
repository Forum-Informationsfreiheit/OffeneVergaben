<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        $this->info('Starting Scrape Job');

        $job = app()->make('App\Jobs\Scrape');

        dispatch($job);

        $this->info('Finished Scrape Job');
    }
}
