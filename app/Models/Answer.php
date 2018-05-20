<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Answer
 * @package App\models
 */
class Answer extends Model
{
    protected $fillable = ['user_id', 'question_id', 'body'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments()
    {
        return $this->morphMany('App\Models\Comment','commentable');
    }
}
