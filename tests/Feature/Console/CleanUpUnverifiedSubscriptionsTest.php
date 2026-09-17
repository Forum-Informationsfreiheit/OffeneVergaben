<?php

namespace Tests\Feature\Console;

use App\Role;
use App\Subscription;
use App\User;
use Tests\Feature\Jobs\JobTestCase;

/**
 * fif:clean-up-subscriptions, which runs hourly.
 */
class CleanUpUnverifiedSubscriptionsTest extends JobTestCase
{
    /**
     * Subscriptions not verified within 60 minutes (the lifetime of the verification link) are removed.
     */
    public function testRemovesSubscriptionsNotVerifiedInTime()
    {
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $expired    = $this->createSubscription($subscriber, ['created_at' => now()->subMinutes(61)]);
        $pending    = $this->createSubscription($subscriber, ['created_at' => now()->subMinutes(59)]);
        $verified   = $this->createSubscription($subscriber, ['created_at' => now()->subDay(), 'verified_at' => now()->subDay()]);

        $this->artisan('fif:clean-up-subscriptions')->assertExitCode(0)->run();

        $this->assertDatabaseMissing('subscriptions', ['id' => $expired->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $pending->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $verified->id]);
        $this->assertDatabaseHas('users', ['id' => $subscriber->id]);
    }

    /**
     * Subscribers who did not verify their email address within 60 minutes are removed. Other users never are.
     */
    public function testRemovesSubscribersWithoutVerifiedEmail()
    {
        $expired    = $this->createUser(Role::SUBSCRIBER, ['email_verified_at' => null, 'created_at' => now()->subMinutes(61)]);
        $pending    = $this->createUser(Role::SUBSCRIBER, ['email_verified_at' => null, 'created_at' => now()->subMinutes(59)]);
        $verified   = $this->createUser(Role::SUBSCRIBER, ['created_at' => now()->subDay()]);
        $registered = $this->createUser(Role::REGISTERED, ['email_verified_at' => null, 'created_at' => now()->subDay()]);
        $editor     = $this->createUser(Role::EDITOR, ['email_verified_at' => null, 'created_at' => now()->subDay()]);

        $this->artisan('fif:clean-up-subscriptions')->assertExitCode(0)->run();

        $this->assertDatabaseMissing('users', ['id' => $expired->id]);
        foreach ([$pending, $verified, $registered, $editor] as $user) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    private function createSubscription(User $subscriber, array $attributes = [])
    {
        return Subscription::forceCreate(array_merge([
            'user_id' => $subscriber->id,
            'type'    => Subscription::TYPE_DATASET,
            'title'   => 'Test-Abo',
            'query'   => 'cpv=99100000',
        ], $attributes));
    }
}
