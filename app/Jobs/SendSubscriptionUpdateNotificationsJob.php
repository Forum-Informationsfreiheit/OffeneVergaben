<?php

namespace App\Jobs;

use App\Dataset;
use App\Http\Filters\DatasetFilter;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendSubscriptionUpdateNotificationsJob
{
    use Dispatchable;

    /**
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = Carbon::now();

        dump('SendSubscriptionUpdateNotificationsJob started at '.$now->toDateTimeString('millisecond'));
        Log::info('SendSubscriptionUpdateNotificationsJob started at '.$now->toDateTimeString('millisecond'));

        // Admins / Editors can be subscribers as well, but they need to have a verified email address
        $verifiedUsers = User::withVerifiedEmail()->get();

        foreach($verifiedUsers as $subscriber) {
            $this->handleSubscriber($subscriber);
        }
    }

    /**
     * @param \App\User $subscriber
     */
    protected function handleSubscriber($subscriber) {

        dump('Handling subscriber (id:'.$subscriber->id.')');
        Log::info('Handling subscriber (id:'.$subscriber->id.')');

        // Get the verified subscriptions
        $subscriptions = $subscriber->subscriptions()
            ->whereNotNull('verified_at')
            ->orderBy('title','asc')
            ->get();

        // No verified subscriptions? Skip this subscriber
        if (!count($subscriptions)) {
            dump('No subscriptions found for subscriber (id:'.$subscriber->id.')');
            Log::info('No subscriptions found for subscriber (id:'.$subscriber->id.')');
            return;
        }

        $updateInfo = [];

        foreach($subscriptions as &$subscription) {
            $now = Carbon::now();

            $timeFrame = [
                // on the very first notification run use created_at timestamp of the subscription
                // otherwise use last_notified_at timestamp of the last notification run
                'from' => $subscription->last_notified_at ? $subscription->last_notified_at : $subscription->created_at,

                // everything that was created up to _now_
                'to' => $now,
            ];

            // get it
            $numberOfUpdates = $this->getNumberOfSubscriptionUpdatesInTimeFrame($timeFrame, $subscription);

            dump('Subscription:'.$subscription->id . ' = '.$numberOfUpdates. ' updates');
            Log::info('Subscription:'.$subscription->id . ' = '.$numberOfUpdates. ' updates');

            $updateInfo[$subscription->id]['new_datasets_count'] = $numberOfUpdates;

            // store the current timestamp
            $subscription->last_notified_at = $now;
            $subscription->save();
        }

        // TODO: actually send the emails (notifications)
        // TODO too: inject subscriptions & updateinfo into mailable/notification
    }

    /**
     * @param array $timeFrame
     * @param \App\Subscription $subscription
     * @return mixed
     */
    protected function getNumberOfSubscriptionUpdatesInTimeFrame($timeFrame, $subscription) {
        $subscriptionQueryString = $subscription->query;

        // The base query is the same that is used for the frontend (/aufträge)
        $query = Dataset::indexQuery();

        // Take the subscription query string and create an 'empty shell' request from it
        // Based on this pseudo request it is easy to create the DatasetFilter object,
        // that handles all customizable where-conditions
        $pseudoRequest = Request::create(route('public::auftraege').'?'.$subscriptionQueryString);
        $filters = new DatasetFilter($pseudoRequest);

        // Now apply the filters (that where created from the subscription query string)
        $query = $filters->apply($query);

        // Only the most recent entries are wanted: apply the date range
        $query->where('datasets.created_at','>',$timeFrame['from']);
        $query->where('datasets.created_at','<=',$timeFrame['to']);

        // Only current versions allowed
        $query->where('datasets.is_current_version',1);

        // Only interested in _new_ datasets
        $query->where('version',1);

        // Finally execute the query and return the number of updates
        $result = $query->get();

        return count($result);
    }
}
