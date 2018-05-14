<?php

namespace App\Models;

use App\Mailer\UserMailer;
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
        'name', 'email', 'password', 'avatar', 'confirmation_token','api_token',
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
        (new UserMailer())->passwordReset($this->name,$this->email,$token);
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
        return $this->follows()->toggle($question);
    }

    /**
     * @param $question
     * @return int
     */
    public function followed($question)
    {
        return  $this->follows()->where('question_id',$question)->count();
    }

    /**
     * 用户正在关注的人
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following()
    {
        return $this->belongsToMany(self::class, 'followers', 'follower_id', 'followed_id')->withTimestamps();
    }

    /**
     * 用户被哪些人所关注（关注者）
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(self::class, 'followers', 'followed_id', 'follower_id')->withTimestamps();
    }

    /**
     * 根据用户id发起关注
     *
     * @param $user
     * @return array
     */
    public function follow($user)
    {
        return $this->following()->toggle($user);
    }
}
