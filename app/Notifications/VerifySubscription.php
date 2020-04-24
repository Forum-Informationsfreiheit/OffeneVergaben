<?php

namespace App\Notifications;

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
                    ->greeting('Hallo!')
                    ->line('jemand hat auf offenevergaben.at diese E-Mail-Adresse für regelmäßige Benachrichtigungen eingetragen.')
                    ->line('Um Ihre E-Mail-Adresse zu bestätigen und diese Benachrichtigungen zu aktivieren, klicken Sie bitte auf folgenden Button:')
                    ->action('E-Mail-Adresse bestätigen & Abo aktivieren', $verificationUrl)
                    ->line('Danke für Ihr Interesse!');
                    //->salutation(null);
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
            Carbon::now()->addMinutes(60),
            [ 'id' => $this->subscription->id, 'email' => $notifiable->email ]
        );
    }
}
