<?php
namespace App\Repositories;

use App\Models\Question;

/**
 * Class QuestionsRepositories
 * @package App\Repositories
 */
class QuestionsRepository
{
    /**
     * @param $id
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function byIdWithTopics($id)
    {
        $question = Question::where('id',$id)->with('topics')->first();
        return $question;
    }

    /**
     * @param array $attributes
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes)
    {
        return Question::create($attributes);
    }

}