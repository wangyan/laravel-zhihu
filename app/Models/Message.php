<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = ['from_user_id', 'to_user_id', 'body'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class,'from_user_id');
    }

    /**
     * @return bool
     */
    public function toUser()
    {
        return !! $this->belongsTo(User::class,'to_user_id');
    }
}
