<?php
namespace App\Channels;

use Illuminate\Notifications\Notification;

/**
 * Class DirectmailChannel
 * @package App\Channels
 */
class DirectmailChannel
{
    /**
     * @param $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toDirectmail($notifiable);
    }

}