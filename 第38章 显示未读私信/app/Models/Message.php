<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Class Message
 * @package App\Models
 */
class Message extends Model
{
    /**
     * @var string
     */
    protected $table = 'messages';

    /**
     * @var array
     */
    protected $fillable = ['from_user_id', 'to_user_id', 'body', 'dialog_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class,'from_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toUser()
    {
        return $this->belongsTo(User::class,'to_user_id');
    }


    /**
     *
     */
    public function markAsRead()
    {
        if(is_null($this->read_at)) {
            $this->forceFill(['has_read' => 'T','read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * @param array $models
     * @return MessageCollection|\Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new MessageCollection($models);
    }

    /**
     * @return bool
     */
    public function read()
    {
        return $this->has_read === 'T';
    }

    /**
     * @return bool
     */
    public function unread()
    {
        return $this->has_read === 'F';
    }

    /**
     * @return bool
     */
    public function shouldAddUnreadClass()
    {
        if(Auth::id() === $this->from_user_id) {
            return false;
        }
        return $this->unread();
    }
}