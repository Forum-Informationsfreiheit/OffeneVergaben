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
        return (new MailMessage)
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
}
