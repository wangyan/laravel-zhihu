<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;

/**
 * Class InboxController
 * @package App\Http\Controllers
 */
class InboxController extends Controller
{
    /**
     * InboxController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $messages = Auth::user()->messages->groupBy('from_user_id');
        return $messages;
        return view('inbox.index',compact('messages'));
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function show($userId)
    {
        $messages = Message::where('from_user_id',$userId)->get();
        return $messages;
    }
}
