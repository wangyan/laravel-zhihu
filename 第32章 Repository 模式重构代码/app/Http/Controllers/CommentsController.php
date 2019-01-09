<?php

namespace App\Http\Controllers;

use App\Repositories\AnswerRepository;
use App\Repositories\CommentRepository;
use App\Repositories\QuestionsRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Class CommentsController
 * @package App\Http\Controllers
 */
class CommentsController extends Controller
{
    /**
     * @var AnswerRepository
     */
    protected $answer;

    /**
     * @var QuestionsRepository
     */
    protected $question;

    /**
     * @var CommentRepository
     */
    protected $comment;

    /**
     * CommentsController constructor.
     * @param AnswerRepository $answer
     * @param QuestionsRepository $question
     * @param CommentRepository $comment
     */
    public function __construct(AnswerRepository $answer, QuestionsRepository $question, CommentRepository $comment)
    {
        $this->answer = $answer;
        $this->question = $question;
        $this->comment = $comment;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function answer($id)
    {
        return $this->answer->getAnswerCommentsById($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function question($id)
    {
        return $this->question->getQuestionCommentsById($id);
    }

    /**
     * @return CommentRepository
     */
    public function store()
    {
        $model = $this->getModelNameFromType(request('type'));

        return $this->comment->create([
            'commentable_id' => request('model'),
            'commentable_type' => $model,
            'user_id' => Auth::guard('api')->user()->id,
            'body' => request('body')
        ]);
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
