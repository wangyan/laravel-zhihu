<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * Class User
 * @package App\Models
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'confirmation_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $data = [
            'title' => env('APP_NAME','Laravel'),
            'name'  => $this->name,
            'url'   => url('password/reset',$token)
        ];
        Mail::send('emails.reset', $data, function ($message) {
            $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
            $message->to($this->email);
            $message->subject('重设密码');
        });
    }

    /**
     * @param Model $model
     * @return bool
     */
    public function owns(Model $model)
    {
        return $this->id == $model->user_id;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * @param $question
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function follows()
    {
        return $this->belongsToMany(Question::class,'user_question')->withTimestamps();
    }

    /**
     * @param $question
     * @return array
     */
    public function followThis($question)
    {
        $this->follows()->toggle($question);
        if ($this->followed($question))
            Question::find($question)->increment('followers_count');
        else
            Question::find($question)->decrement('followers_count');
    }

    /**
     * @param $question
     * @return int
     */
    public function followed($question)
    {
        return  $this->follows()->where('question_id',$question)->count();
    }
}
