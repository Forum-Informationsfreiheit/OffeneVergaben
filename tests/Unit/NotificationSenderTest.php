<?php

namespace Tests\Unit;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionUpdateSummary;
use App\Notifications\VerifySubscription;
use Tests\TestCase;

/**
 * The senders come from config/mail.php, not from env(): env() returns null outside config files once the
 * configuration is cached (php artisan config:cache).
 */
class NotificationSenderTest extends TestCase
{
    public function testVerificationIsSentFromAlertsAddress()
    {
        config(['mail.alerts_from_address' => 'alerts@offenevergaben.example', 'app.name' => 'OffeneVergaben.at']);
        $user = (new User())->forceFill(['id' => 1, 'email' => 'abo@example.invalid']);
        $subscription = (new Subscription())->forceFill(['id' => 2, 'user_id' => 1]);

        $mail = (new VerifySubscription($subscription))->toMail($user);

        $this->assertSame(['alerts@offenevergaben.example', 'OffeneVergaben.at'], $mail->from);
    }

    public function testSummaryIsSentFromDefaultAddress()
    {
        config(['mail.default_from_address' => 'noreply@offenevergaben.example', 'app.name' => 'OffeneVergaben.at']);

        $mail = (new SubscriptionUpdateSummary([], []))->toMail(new User());

        $this->assertSame(['noreply@offenevergaben.example', 'OffeneVergaben.at'], $mail->from);
    }
}
