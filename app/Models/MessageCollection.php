<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Class MessageCollection
 * @package App
 */
class MessageCollection extends Collection
{

    /**
     * Mark a notifications collection as read.
     */
    public function markAsRead()
    {
        $this->each(function($message) {
            if($message->to_user_id === Auth::id() ){
                $message->markAsRead();
            }
        });
    }
}