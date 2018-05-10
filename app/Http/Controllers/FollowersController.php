<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use Auth;

class FollowersController extends Controller
{
    protected $user;

    /**
     * FollowersController constructor.
     * @param UserRepository $user
     */
    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($id)
    {
        $user = $this->user->byId($id);
        $followers = $user->followers()->pluck('follower_id')->toArray();
        if ( in_array(Auth::guard('api')->user()->id, $followers) ) {
            return response()->json(['followed' => true]);
        }
        return response()->json(['followed' => false]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function follow()
    {
        $user = $this->user->byId(request('user'));
        $followed = Auth::guard('api')->user()->follow($user->id);
        if ( count($followed['attached']) > 0 ) {
            $user->increment('followers_count');
            return response()->json(['followed' => true]);
        }
        $user->decrement('followers_count');
        return response()->json(['followed' => false]);
    }
}
