<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionUpdateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:send-subscription-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends the subscription update notifications.';

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
        $this->info('Starting to send subscription update notifications.');
        Log::info('Starting to send subscription update notifications.');

        $params = [ ];

        $job = app()->make('App\Jobs\SendSubscriptionUpdateNotificationsJob',$params);
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished sending subscription update notifications in '.$runtime.' seconds.');
        Log::info('Finished sending subscription update notifications in '.$runtime.' seconds.');
    }
}
