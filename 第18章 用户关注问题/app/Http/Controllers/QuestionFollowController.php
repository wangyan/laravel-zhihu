<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class QuestionFollowController
 * @package App\Http\Controllers
 */
class QuestionFollowController extends Controller
{

    /**
     * QuestionFollowController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param $question
     * @return \Illuminate\Http\RedirectResponse
     */
    public function follow($question)
    {
        Auth::user()->follows($question);
        Question::find($question)->increment('followers_count');
        return back();
    }
}
