<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Follow
 * @package App\models
 */
class Follow extends Model
{
    /**
     * @var string
     */
    protected $table = 'user_question';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'question_id'];
}
