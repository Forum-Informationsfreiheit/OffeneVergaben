<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendSubscriptionUpdateNotificationsJob;
use App\Notifications\SubscriptionUpdateSummary;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class SendSubscriptionUpdateNotificationsJobTest extends JobTestCase
{
    // as stored by the subscribe form for /aufträge?cpv=99100000
    const QUERY = 'cpv=99100000';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->createCpv('99100000');
    }

    /**
     * Counted are new datasets (version 1, current) matching the query, created since the subscription was created.
     */
    public function testSendsNumberOfNewMatchingDatasets()
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER);
        $subscription = $this->createSubscription($subscriber, ['created_at' => now()->subDay()]);

        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour()]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour()]);
        // not counted: other cpv, created before the subscription, update of an older dataset, older version
        $this->createDataset(['created_at' => now()->subHour()]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subDays(2)]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour(), 'version' => 2]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour(), 'is_current_version' => 0]);

        (new SendSubscriptionUpdateNotificationsJob())->handle();

        $this->assertSame(2, $this->sentCount($subscriber, $subscription));
        $this->assertNotNull($subscription->fresh()->last_notified_at);
    }

    /**
     * After the first run, only datasets created since the last notification count.
     */
    public function testCountsDatasetsSinceLastNotification()
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER);
        $subscription = $this->createSubscription($subscriber, [
            'created_at'       => now()->subDays(3),
            'last_notified_at' => now()->subDay(),
        ]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subDays(2)]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour()]);

        (new SendSubscriptionUpdateNotificationsJob())->handle();

        $this->assertSame(1, $this->sentCount($subscriber, $subscription));
    }

    public function testSendsNothingWithoutNewDatasets()
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER);
        $subscription = $this->createSubscription($subscriber, ['created_at' => now()->subDay()]);

        (new SendSubscriptionUpdateNotificationsJob())->handle();

        Notification::assertNotSentTo($subscriber, SubscriptionUpdateSummary::class);
        // the next run starts from now
        $this->assertNotNull($subscription->fresh()->last_notified_at);
    }

    public function testIgnoresUnverifiedSubscriptionsAndSubscribers()
    {
        $unverifiedSubscriber = $this->createUser(Role::SUBSCRIBER, ['email_verified_at' => null]);
        $verifiedSubscription = $this->createSubscription($unverifiedSubscriber, ['created_at' => now()->subDay()]);
        $subscriber           = $this->createUser(Role::SUBSCRIBER);
        $unverifiedSubscription = $this->createSubscription($subscriber, [
            'created_at'  => now()->subDay(),
            'verified_at' => null,
        ]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour()]);

        (new SendSubscriptionUpdateNotificationsJob())->handle();

        Notification::assertNotSentTo($unverifiedSubscriber, SubscriptionUpdateSummary::class);
        Notification::assertNotSentTo($subscriber, SubscriptionUpdateSummary::class);
        $this->assertNull($verifiedSubscription->fresh()->last_notified_at);
        $this->assertNull($unverifiedSubscription->fresh()->last_notified_at);
    }

    /**
     * Editors and admins with a verified email address get notifications for their subscriptions, too.
     */
    public function testNotifiesEditorsWithSubscriptions()
    {
        $editor       = $this->createUser(Role::EDITOR);
        $subscription = $this->createSubscription($editor, ['created_at' => now()->subDay()]);
        $this->createDataset(['cpv_code' => '99100000', 'created_at' => now()->subHour()]);

        (new SendSubscriptionUpdateNotificationsJob())->handle();

        $this->assertSame(1, $this->sentCount($editor, $subscription));
    }

    /**
     * The number of new datasets for the subscription in the summary sent to the user.
     */
    private function sentCount(User $user, Subscription $subscription)
    {
        $notifications = Notification::sent($user, SubscriptionUpdateSummary::class);
        $this->assertCount(1, $notifications, 'Expected one SubscriptionUpdateSummary.');

        return $notifications->first()->toMail($user)->viewData['updateInfo'][$subscription->id]['new_datasets_count'];
    }

    private function createSubscription(User $subscriber, array $attributes = [])
    {
        return Subscription::forceCreate(array_merge([
            'user_id'     => $subscriber->id,
            'type'        => Subscription::TYPE_DATASET,
            'title'       => 'Test-Abo',
            'query'       => self::QUERY,
            'verified_at' => now()->subDay(),
        ], $attributes));
    }
}
