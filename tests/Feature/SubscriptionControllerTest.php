<?php

namespace Tests\Feature;

use App\Http\Controllers\SubscriptionController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Notifications\VerifySubscription;
use App\Role;
use App\Subscription;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Tests of the public subscription routes in routes/web.php: the subscribe form and the signed links in
 * the verification and update summary mails.
 *
 * Notifications are faked, no mail is sent.
 */
class SubscriptionControllerTest extends PublicTestCase
{
    // a filter query as DatasetController@index puts it into the subscribe form
    const QUERY = 'cpv=45000000';

    protected function setUp(): void
    {
        parent::setUp();

        // the subscribe form is sent without CSRF token, independent of APP_ENV
        $this->withoutMiddleware(VerifyCsrfToken::class);

        Notification::fake();
    }

    public function testSubscribeCreatesSubscriberAndSendsVerification()
    {
        $input = $this->subscribeInput();

        $response = $this->from(route('public::auftraege'))->post('/subscribe', $input);

        $response->assertRedirect(route('public::auftraege'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('subscribed', true);
        $this->assertDatabaseHas('users', [
            'email'             => $input['email'],
            'role_id'           => Role::SUBSCRIBER,
            'email_verified_at' => null,
        ]);

        $subscriber = User::where('email', $input['email'])->first();
        $this->assertDatabaseHas('subscriptions', [
            'user_id'     => $subscriber->id,
            'type'        => Subscription::TYPE_DATASET,
            'title'       => $input['title'],
            'query'       => self::QUERY,
            'verified_at' => null,
        ]);
        Notification::assertSentTo($subscriber, VerifySubscription::class);
    }

    /**
     * The subscription is added to the existing user, whose role stays the same.
     */
    public function testSubscribeWithKnownEmailAddsSubscriptionToExistingUser()
    {
        $editor = $this->createUser(Role::EDITOR);

        $response = $this->from(route('public::auftraege'))
            ->post('/subscribe', $this->subscribeInput(['email' => $editor->email]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $editor->id, 'role_id' => Role::EDITOR]);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $editor->id, 'query' => self::QUERY]);
        Notification::assertSentTo($editor, VerifySubscription::class);
    }

    public function testSubscribeRejectsSameQueryTwiceForOneEmail()
    {
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $this->createSubscription($subscriber);

        $response = $this->from(route('public::auftraege'))
            ->post('/subscribe', $this->subscribeInput(['email' => $subscriber->email]));

        $response->assertSessionHasErrors('query', null, 'subscription');
        $this->assertSame(1, $subscriber->subscriptions()->count());
        Notification::assertNothingSent();
    }

    /**
     * @dataProvider invalidInputProvider
     */
    public function testSubscribeValidatesInput($input, $field)
    {
        $input = $this->subscribeInput($input);

        $response = $this->from(route('public::auftraege'))->post('/subscribe', $input);

        $response->assertRedirect(route('public::auftraege'));
        $response->assertSessionHasErrors($field, null, 'subscription');
        $this->assertDatabaseMissing('users', ['email' => $input['email']]);
        Notification::assertNothingSent();
    }

    /**
     * The query must be exactly what DatasetFilter makes of it (see DatasetController@index), so the
     * duplicate check can compare query strings.
     *
     * @dataProvider invalidQueryProvider
     */
    public function testSubscribeRejectsInvalidQuery($query)
    {
        $input = $this->subscribeInput(['query' => $query]);

        $response = $this->from(route('public::auftraege'))->post('/subscribe', $input);

        $response->assertRedirect(route('public::auftraege'));
        $response->assertSessionHas('flash_notification', function ($messages) {
            return $messages->first()->level === 'danger';
        });
        $this->assertDatabaseMissing('users', ['email' => $input['email']]);
        Notification::assertNothingSent();
    }

    /**
     * Only bots fill the hidden honeypot field pm_title. Their request is dropped without any feedback.
     */
    public function testHoneypotRequestIsIgnored()
    {
        $input = $this->subscribeInput(['pm_title' => 'Aufträge ab 1 Mio']);

        $response = $this->from(route('public::auftraege'))->post('/subscribe', $input);

        $response->assertRedirect(route('public::auftraege'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('subscribed');
        $this->assertDatabaseMissing('users', ['email' => $input['email']]);
        Notification::assertNothingSent();
    }

    /**
     * In production an error while subscribing (e.g. the mail server is down) is logged and shown as a message.
     */
    public function testSubscribeErrorInProductionIsShownAsMessage()
    {
        $this->app['env'] = 'production';
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('Mailserver nicht erreichbar'));
        Log::shouldReceive('info');
        Log::shouldReceive('error')->once();

        $response = $this->from(route('public::auftraege'))->post('/subscribe', $this->subscribeInput());

        $response->assertRedirect(route('public::auftraege'));
        $response->assertSessionHas('subscribed', false);
        $response->assertSessionHas('flash_notification', function ($messages) {
            return $messages->first()->level === 'danger';
        });
    }

    /**
     * Outside of production the error is thrown, so it shows up while developing.
     */
    public function testSubscribeErrorOutsideOfProductionIsThrown()
    {
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('Mailserver nicht erreichbar'));
        Log::shouldReceive('info');
        Log::shouldReceive('error');

        $response = $this->from(route('public::auftraege'))->post('/subscribe', $this->subscribeInput());

        $response->assertStatus(500);
    }

    public function testVerificationLinkVerifiesSubscriptionAndEmail()
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER, ['email_verified_at' => null]);
        $subscription = $this->createSubscription($subscriber);

        $response = $this->get($this->verificationUrl($subscription));

        $response->assertRedirect(route('public::auftraege'));
        $this->assertNotNull($subscription->fresh()->verified_at);
        $this->assertNotNull($subscriber->fresh()->email_verified_at);
    }

    public function testVerificationLinkKeepsEarlierEmailVerification()
    {
        $verifiedAt   = Carbon::parse('2020-05-01 12:00:00');
        $subscriber   = $this->createUser(Role::SUBSCRIBER, ['email_verified_at' => $verifiedAt]);
        $subscription = $this->createSubscription($subscriber);

        $response = $this->get($this->verificationUrl($subscription));

        $response->assertRedirect(route('public::auftraege'));
        $this->assertEquals($verifiedAt, $subscriber->fresh()->email_verified_at);
    }

    public function testExpiredVerificationLinkIsForbidden()
    {
        $subscription = $this->createSubscription($this->createUser(Role::SUBSCRIBER));
        $url          = $this->verificationUrl($subscription);

        Carbon::setTestNow(now()->addMinutes(Subscription::VERIFY_SUBSCRIPTION_IN_MINUTES + 1));
        $response = $this->get($url);

        $response->assertForbidden();
        $this->assertNull($subscription->fresh()->verified_at);
    }

    /**
     * @dataProvider signedRoutesProvider
     */
    public function testUnsignedLinkIsForbidden($routeName)
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER);
        $subscription = $this->createSubscription($subscriber, ['verified_at' => now()]);

        $response = $this->get(route($routeName, ['id' => $subscription->id, 'email' => $subscriber->email]));

        $response->assertForbidden();
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);
    }

    /**
     * The signature covers the whole URL, so a link can't be changed to point to another subscription.
     *
     * @dataProvider signedRoutesProvider
     */
    public function testLinkWithChangedIdIsForbidden($routeName)
    {
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $own        = $this->createSubscription($subscriber, ['verified_at' => now()]);
        $other      = $this->createSubscription($this->createUser(Role::SUBSCRIBER), ['verified_at' => now()]);
        $url        = URL::signedRoute($routeName, ['id' => $own->id, 'email' => $subscriber->email]);

        $response = $this->get(str_replace("/subscriptions/{$own->id}/", "/subscriptions/{$other->id}/", $url));

        $response->assertForbidden();
        $this->assertDatabaseHas('subscriptions', ['id' => $other->id]);
    }

    /**
     * Links in old mails can point to a deleted subscription (unsubscribed, or not verified in time).
     *
     * @dataProvider signedRoutesProvider
     */
    public function testLinkToDeletedSubscriptionReturnsNotFound($routeName)
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER);
        $subscription = $this->createSubscription($subscriber);
        $url          = URL::signedRoute($routeName, ['id' => $subscription->id, 'email' => $subscriber->email]);
        $subscription->delete();

        $response = $this->get($url);

        $response->assertNotFound();
    }

    public function testCancelLinkShowsConfirmationPage()
    {
        $subscription = $this->createSubscription($this->createUser(Role::SUBSCRIBER), [
            'title'       => 'Abo Straßenbau',
            'verified_at' => now(),
        ]);

        $response = $this->get($subscription->cancelUrl);

        $response->assertStatus(200);
        $response->assertViewIs('public.subscriptions.cancel');
        $response->assertSee('Abo Straßenbau');
        $response->assertSee($subscription->unsubscribeUrl);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);
    }

    public function testCancelAllLinkListsVerifiedSubscriptions()
    {
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $this->createSubscription($subscriber, ['title' => 'Abo Straßenbau', 'verified_at' => now()]);
        $this->createSubscription($subscriber, ['title' => 'Abo Reinigung', 'query' => 'cpv=90000000']);

        $response = $this->get($subscriber->cancelAllSubscriptionsUrl);

        $response->assertStatus(200);
        $response->assertViewIs('public.subscriptions.cancel-all');
        $response->assertSee('Abo Straßenbau');
        $response->assertDontSee('Abo Reinigung');
        $response->assertSee($subscriber->unsubscribeAllUrl);
    }

    /**
     * TODO: bug — SubscriptionController@cancelAll calls $subscriber->subscriptions() before the null check,
     * so the link fails with HTTP 500 for unknown emails and for users that aren't subscribers. Editors and
     * admins who subscribed with their account's email get this link in every update summary mail. Move the
     * null check up, then remove markTestIncomplete() here.
     */
    public function testCancelAllLinkForOtherRolesReturnsNotFound()
    {
        $this->markTestIncomplete('SubscriptionController@cancelAll uses $subscriber before the null check (HTTP 500).');

        $editor = $this->createUser(Role::EDITOR);
        $this->createSubscription($editor, ['verified_at' => now()]);

        $response = $this->get($editor->cancelAllSubscriptionsUrl);

        $response->assertNotFound();
    }

    public function testUnsubscribeLinkDeletesOnlyThatSubscription()
    {
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $cancelled  = $this->createSubscription($subscriber, ['verified_at' => now()]);
        $kept       = $this->createSubscription($subscriber, ['verified_at' => now(), 'query' => 'cpv=90000000']);

        $response = $this->get($cancelled->unsubscribeUrl);

        $response->assertRedirect(route('public::auftraege'));
        $this->assertDatabaseMissing('subscriptions', ['id' => $cancelled->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $kept->id]);
        $this->assertDatabaseHas('users', ['id' => $subscriber->id]);
    }

    public function testUnsubscribeAllLinkDeletesSubscriberAndSubscriptions()
    {
        $subscriber   = $this->createUser(Role::SUBSCRIBER);
        $subscription = $this->createSubscription($subscriber, ['verified_at' => now()]);

        $response = $this->get($subscriber->unsubscribeAllUrl);

        $response->assertRedirect(route('public::auftraege'));
        $this->assertDatabaseMissing('users', ['id' => $subscriber->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    /**
     * Only subscribers are deleted, so editors and admins can't delete their account through a mail link.
     */
    public function testUnsubscribeAllLinkKeepsUsersWithOtherRoles()
    {
        $editor = $this->createUser(Role::EDITOR);

        $response = $this->get($editor->unsubscribeAllUrl);

        $response->assertRedirect(route('public::auftraege'));
        $this->assertDatabaseHas('users', ['id' => $editor->id]);
    }

    public function testUnsubscribeAllLinkForUnknownEmailReturnsNotFound()
    {
        $response = $this->get(URL::signedRoute('public::unsubscribe', [
            'id'    => SubscriptionController::ALL,
            'email' => 'unknown-'.Str::random(10).'@example.test',
        ]));

        $response->assertNotFound();
    }

    public function invalidInputProvider()
    {
        return [
            'title missing'         => [['title' => ''], 'title'],
            'title too long'        => [['title' => str_repeat('a', 191)], 'title'],
            'email missing'         => [['email' => ''], 'email'],
            'email invalid'         => [['email' => 'no-email'], 'email'],
            'privacy not confirmed' => [['confirm' => ''], 'confirm'],
            'query missing'         => [['query' => ''], 'query'],
        ];
    }

    public function invalidQueryProvider()
    {
        return [
            'unknown filter'     => ['foo=bar'],
            'invalid cpv code'   => ['cpv=abc'],
            'filters not sorted' => ['volume_from=1000&cpv=45000000'],
        ];
    }

    public function signedRoutesProvider()
    {
        return [
            'verify'      => ['public::verify-subscription'],
            'cancel'      => ['public::cancel-subscription'],
            'unsubscribe' => ['public::unsubscribe'],
        ];
    }

    private function subscribeInput(array $overrides = [])
    {
        return array_merge([
            'title'   => 'Abo Straßenbau',
            'email'   => 'subscriber-'.Str::random(10).'@example.test',
            'confirm' => '1',
            'query'   => self::QUERY,
        ], $overrides);
    }

    private function createSubscription(User $subscriber, array $attributes = [])
    {
        return Subscription::forceCreate(array_merge([
            'user_id' => $subscriber->id,
            'type'    => Subscription::TYPE_DATASET,
            'title'   => 'Test-Abo',
            'query'   => self::QUERY,
        ], $attributes));
    }

    /**
     * The link as it is sent in the VerifySubscription mail.
     */
    private function verificationUrl(Subscription $subscription)
    {
        return (new VerifySubscription($subscription))->toMail($subscription->subscriber)->actionUrl;
    }
}
