<?php

namespace App\Notifications;

use App\Subscription;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifySubscription extends Notification
{
    protected $subscription;

    /**
     * Create a new notification instance.
     *
     * @param \App\Subscription $subscription
     */
    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if (!$notifiable instanceof User) {
            throw new \InvalidArgumentException("Notifiable must be instance of App\\User");
        }

        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
                    ->from($this->mailFromAddress(),config('app.name'))
                    ->subject(config('app.name').' - Abo bestätigen')
                    ->greeting('Hallo!')
                    ->line('jemand hat auf '.config('app.name').' diese E-Mail-Adresse für regelmäßige Benachrichtigungen eingetragen.')
                    ->line('Um Ihre E-Mail-Adresse zu bestätigen und diese Benachrichtigungen zu aktivieren, klicken Sie bitte auf folgenden Link:')
                    ->action('E-Mail-Adresse bestätigen & Abo aktivieren', $verificationUrl)
                    ->line('Danke für Ihr Interesse!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    protected function verificationUrl($notifiable) {
        return URL::temporarySignedRoute(
            'public::verify-subscription',
            Carbon::now()->addMinutes(Subscription::VERIFY_SUBSCRIPTION_IN_MINUTES),
            [ 'id' => $this->subscription->id, 'email' => $notifiable->email ]
        );
    }

    protected function mailFromAddress() {
        return env('APP_MAIL_ALERTS_FROM_ADDRESS',env('MAIL_FROM_ADDRESS'));
    }
}
