<?php

namespace App\Http\Controllers;

use App\models\Answer;
use App\Models\Comment;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentsController extends Controller
{
    /**
     * @param $id
     * @return mixed
     */
    public function answer($id)
    {
        $answer = Answer::with('comments','comments.user')->where('id', $id)->first();
        return $answer->comments;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function question($id)
    {
        $question = Question::with('comments','comments.user')->where('id', $id)->first();
        return $question->comments;

    }

    /**
     * @return mixed
     */
    public function store()
    {
        $model = $this->getModelNameFromType(request('type'));

        $comment = Comment::create([
            'commentable_id' => request('model'),
            'commentable_type' => $model,
            'user_id' => Auth::guard('api')->user()->id,
            'body' => request('body')
        ]);

        return $comment;
    }

    /**
     * @param $type
     * @return string
     */
    private function getModelNameFromType($type)
    {
        return $type === 'question' ? 'App\Models\Question' : 'App\Models\Answer';
    }
}
