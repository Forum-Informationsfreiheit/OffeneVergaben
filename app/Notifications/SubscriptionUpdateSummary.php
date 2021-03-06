<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionUpdateSummary extends Notification
{
    protected $subscriptions;

    protected $updateInfo;

    /**
     * @param \Illuminate\Support\Collection $subscriptions
     * @param array $updateInfo
     */
    public function __construct($subscriptions, $updateInfo)
    {
        $this->subscriptions = $subscriptions;
        $this->updateInfo = $updateInfo;
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
        // notification text lines come from the blade template mail.subscription.update-summary
        return (new MailMessage)
            ->from($this->mailFromAddress(),config('app.name'))
            ->subject('Neuigkeiten von '.config('app.name'))
            ->markdown('mail.subscription.update-summary',[
                'subscriber' => $notifiable,
                'subscriptions' => $this->subscriptions,
                'updateInfo' => $this->updateInfo,
            ]);
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

    protected function mailFromAddress() {
        return env('APP_MAIL_DEFAULT_FROM_ADDRESS',env('MAIL_FROM_ADDRESS'));
    }
}
