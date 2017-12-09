<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','body','user_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany;
     */
    public function topics()
    {
        return $this->belongsToMany(Topic::class)->withTimestamps();
    }
}
