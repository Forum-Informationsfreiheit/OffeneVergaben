<?php

namespace App\Console\Commands;

use App\Role;
use App\Subscription;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanUpUnverifiedSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:clean-up-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes subscriptions (and related users) that were not verified in time.';

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
        $this->info('Starting Clean Up job for unverified subscriptions.');
        Log::info('Starting Clean Up job for unverified subscriptions.');

        $olderThan = Carbon::now()->subMinutes(Subscription::VERIFY_SUBSCRIPTION_IN_MINUTES);

        // Two step process, first remove all unverified subscriptions that are older than x
        $query = Subscription::where('verified_at',null)
            ->where('created_at','<',$olderThan->format('Y-m-d H:i:s'));
        $subscriptions = $query->get();

        foreach($subscriptions as $subscription) {
            $this->warn('Removing subscription '.$subscription->title.' of subscriber '.$subscription->subscriber->email);
            Log::info('Removing subscription '.$subscription->title.' of subscriber '.$subscription->subscriber->email);

            $subscription->delete();
        }

        // Second remove subscribers (from user table) that have no verified email (and are older than x)
        $query = User::where('role_id',Role::SUBSCRIBER)
            ->where('email_verified_at',null)
            ->where('created_at','<',$olderThan->format('Y-m-d H:i:s'));
        $subscribers = $query->get();

        foreach($subscribers as $subscriber) {
            // additional safety check (will never run if the query above performs correctly)
            // --> never delete any registered users of the site
            if ($subscriber->role_id > Role::SUBSCRIBER) {
                continue;
            }

            $this->warn('Deleting unverified subscriber '.$subscriber->email);
            Log::info('Deleting unverified subscriber '.$subscriber->email);

            $subscriber->delete();
        }

        $this->info('Finished Clean Up job for unverified subscriptions.');
        Log::info('Finished Clean Up job for unverified subscriptions.');
    }
}
