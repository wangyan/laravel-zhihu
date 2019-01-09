<?php

namespace App\Http\Controllers;

use App\Repositories\AnswerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VotesController extends Controller
{
    protected $answer;

    /**
     * VotesController constructor.
     * @param $answer
     */
    public function __construct(AnswerRepository $answer)
    {
        $this->answer = $answer;
    }

    /**
     * 根据用户id查询是否已经点赞
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function users($id)
    {
        $user = Auth::guard('api')->user();
        if($user->hasVoteFor($id)){
            return response()->json(['voted' => true]);
        }
        return response()->json(['voted' => false]);
    }

    /**
     * 点赞
     *
     * @param $answer
     * @return \Illuminate\Http\JsonResponse
     */
    public function vote()
    {
        $answer = $this->answer->byId(request('answer'));
        $voted = Auth::guard('api')->user()->voteFor($answer);
        if ( count($voted['attached']) > 0 ) {
            $answer->increment('votes_count');
            return response()->json(['voted' => true]);
        }
        $answer->decrement('votes_count');
        return response()->json(['voted' => false]);
    }
}
