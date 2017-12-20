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

    /**
     * @param $query
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('is_hidden','F');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
