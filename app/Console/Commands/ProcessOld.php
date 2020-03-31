<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated
 *
 * Class ProcessOld
 * @package App\Console\Commands
 */
class ProcessOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:process_deprecated';

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

        $job = app()->make('App\Jobs\Process');
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished Process Job in '.$runtime.' seconds');
        Log::channel('processor_daily')->info('Finished Process Job in '.$runtime.' seconds');
    }
}
