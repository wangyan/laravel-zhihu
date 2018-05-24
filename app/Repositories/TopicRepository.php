<?php

namespace App\Repositories;

use App\Models\Topic;
use Illuminate\Http\Request;

/**
 * Class TopicRepository
 * @package App\Repositories
 */
class TopicRepository
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getTopicsForTagging(Request $request)
    {
        return Topic::select(['id','name'])
            ->where('name','like','%'.$request->query('q').'%')
            ->get();
    }
}