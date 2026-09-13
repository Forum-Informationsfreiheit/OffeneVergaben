<?php

namespace Tests\Feature\Admin;

use App\Notifications\VerifySubscription;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubscriptionControllerTest extends AdminTestCase
{
    public function testIndexFiltersBySubscriber()
    {
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $other      = $this->createUser(Role::SUBSCRIBER);
        $this->createSubscription($subscriber->id, 'Abo Straßenbau');
        $this->createSubscription($other->id, 'Abo Reinigung');

        $response = $this->actingAs($this->createEditor())->get('/admin/subscriptions?subscriber_id='.$subscriber->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscriptions.index');
        $response->assertViewHas('total', 1);
        $response->assertSee('Abo Straßenbau');
        $response->assertDontSee('Abo Reinigung');
    }

    public function testAdminCanResendVerificationNotification()
    {
        Notification::fake();
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        $id         = $this->createSubscription($subscriber->id);

        $response = $this->actingAs($this->createAdmin())
            ->from(route('admin::subscriptions'))
            ->patch('/admin/subscriptions/resend-verification-notification', ['id' => $id]);

        $response->assertRedirect(route('admin::subscriptions'));
        Notification::assertSentTo($subscriber, VerifySubscription::class);
    }

    public function testEditorCannotResendVerificationNotification()
    {
        Notification::fake();
        $id = $this->createSubscription($this->createUser(Role::SUBSCRIBER)->id);

        $response = $this->actingAs($this->createEditor())
            ->patch('/admin/subscriptions/resend-verification-notification', ['id' => $id]);

        $response->assertForbidden();
        Notification::assertNothingSent();
    }

    public function testResendForUnknownSubscriptionReturnsNotFound()
    {
        $response = $this->actingAs($this->createAdmin())
            ->patch('/admin/subscriptions/resend-verification-notification', ['id' => 0]);

        $response->assertNotFound();
    }

    public function testAdminCanDeleteSubscription()
    {
        $id = $this->createSubscription($this->createUser(Role::SUBSCRIBER)->id);

        $response = $this->actingAs($this->createAdmin())->delete('/admin/subscriptions/destroy', ['id' => $id]);

        $response->assertRedirect(route('admin::subscriptions'));
        $this->assertDatabaseMissing('subscriptions', ['id' => $id]);
    }

    public function testEditorCannotDeleteSubscription()
    {
        $id = $this->createSubscription($this->createUser(Role::SUBSCRIBER)->id);

        $response = $this->actingAs($this->createEditor())->delete('/admin/subscriptions/destroy', ['id' => $id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('subscriptions', ['id' => $id]);
    }

    private function createSubscription($userId, $title = 'Test-Abo')
    {
        return DB::table('subscriptions')->insertGetId([
            'user_id'    => $userId,
            'type'       => 'dataset',
            'title'      => $title,
            'query'      => 'cpv=45',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
