<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendSubscriptionUpdateNotificationsJob
{
    use Dispatchable;

    /**
     * Subscribers whose notification could not be sent during this run.
     *
     * @var array<int, array{id: int, email: string, error: string}>
     */
    protected $failedRecipients = [];

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
        Log::info('SendSubscriptionUpdateNotificationsJob started at '.Carbon::now()->toDateTimeString('millisecond'));

        // Admins / Editors can be subscribers as well, but they need to have a verified email address
        $verifiedUsers = User::withVerifiedEmail()->get();

        foreach($verifiedUsers as $subscriber) {
            $this->handleSubscriber($subscriber);
        }

        if (count($this->failedRecipients)) {
            dump(count($this->failedRecipients).' notification(s) could not be sent', $this->failedRecipients);
            Log::warning(count($this->failedRecipients).' SubscriptionUpdateSummaryNotification(s) could not be sent', [
                'failed_recipients' => $this->failedRecipients,
            ]);
        }

        Log::info('SendSubscriptionUpdateNotificationsJob finished at '.Carbon::now()->toDateTimeString('millisecond'));
    }

    /**
     * @param \App\Models\User $subscriber
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
            return;
        }

        $updateInfo = [];
        $totalUpdates = 0;

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
            $totalUpdates += $numberOfUpdates;

            $updateInfo[$subscription->id] = [
                'new_datasets_count' => $numberOfUpdates
            ];

            // remember the end of the time frame; it is only stored once the
            // notification went out, so a failed send is retried on the next run
            $subscription->last_notified_at = $now;
        }

        dump("$totalUpdates total updates for ".count($subscriptions)." subscriptions");
        Log::info("$totalUpdates total updates for ".count($subscriptions).' subscriptions of subscriber (id:'.$subscriber->id.')');

        if ($totalUpdates > 0) {
            dump('Sending SubscriptionUpdateSummaryNotification to subscriber (id:'.$subscriber->id.')');
            Log::info('Sending SubscriptionUpdateSummaryNotification to subscriber (id:'.$subscriber->id.')');

            try {
                $subscriber->sendSubscriptionUpdateSummaryNotification($subscriptions, $updateInfo);
            } catch (TransportExceptionInterface $e) {
                // e.g. "550 Recipient not found": skip this subscriber and keep going
                // with the others; last_notified_at stays untouched
                dump('Failed to send SubscriptionUpdateSummaryNotification to subscriber (id:'.$subscriber->id.'): '.$e->getMessage());
                Log::warning('Failed to send SubscriptionUpdateSummaryNotification to subscriber (id:'.$subscriber->id.')', [
                    'exception' => $e->getMessage(),
                ]);
                $this->failedRecipients[] = [
                    'id'    => $subscriber->id,
                    'email' => $subscriber->email,
                    'error' => $e->getMessage(),
                ];
                return;
            }
        }

        foreach ($subscriptions as $notifiedSubscription) {
            $notifiedSubscription->save();
        }
    }

    /**
     * @param array $timeFrame
     * @param \App\Models\Subscription $subscription
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
