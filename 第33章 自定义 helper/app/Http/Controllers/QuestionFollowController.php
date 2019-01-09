<?php

namespace App\Http\Controllers;

use App\Repositories\QuestionsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class QuestionFollowController
 * @package App\Http\Controllers
 */
class QuestionFollowController extends Controller
{
    /**
     * @var QuestionsRepository
     */
    protected $question;

    /**
     * QuestionFollowController constructor.
     * @param QuestionsRepository $question
     */
    public function __construct(QuestionsRepository $question)
    {
        $this->question = $question;
        $this->middleware('auth');
    }

    /**
     * @param $question
     * @return \Illuminate\Http\RedirectResponse
     */
    public function follow($question)
    {
        Auth::user()->followThis($question);
        return back();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function follower(Request $request)
    {
        $user = Auth::guard('api')->user();
        $followed = $user->followed($request->get('question'));
        if ($followed) {
            return response()->json(['followed' => true]);
        }
        return response()->json(['followed' => false]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function followThisQuestion(Request $request)
    {
        $user = Auth::guard('api')->user();
        $question = $this->question->byID($request->get('question'));
        $followed = $user->followThis($question->id);
        if (count($followed['detached']) > 0){
            $question->decrement('followers_count');
            return response()->json(['followed' => false]);
        }
        $question->increment('followers_count');
        return response()->json(['followed' => true]);
    }
}