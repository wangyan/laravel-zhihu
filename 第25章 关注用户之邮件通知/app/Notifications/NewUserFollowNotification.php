<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Auth;
use App\Channels\SendcloudChannel;
use Mail;

class NewUserFollowNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database',SendcloudChannel::class];
    }

    /**
     * @param $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'name' => Auth::guard('api')->user()->name,
        ];
    }

    /**
     * @param $notifiable
     */
    public function toSendcloud($notifiable)
    {
        $data = [
            'yourName' => $notifiable->name,
            'followerName' => Auth::guard('api')->user()->name,
            'url'  => 'http://zhihu.dev/user/'.Auth::guard('api')->user()->id,
        ];
        Mail::send('emails.follow', $data, function ($message) use ($data,$notifiable) {
            $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
            $message->to($notifiable->email);
            $message->subject($data['followerName'].'关注了你');
        });
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
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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
