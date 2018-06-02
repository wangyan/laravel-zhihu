<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }
    public function via()
    {
        return ['database'];
    }

    public function toDatabase()
    {
         return [
             'name' => $this->message->fromUser->name,
             'dialog' => $this->message->dialog_id,
         ];
    }
}
