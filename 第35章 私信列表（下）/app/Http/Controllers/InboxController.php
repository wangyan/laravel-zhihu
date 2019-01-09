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
        $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser','toUser'])->get();
        return view('inbox.index',['messages' => $messages->groupBy('to_user_id')]);
    }

    /**
     * @param $dialogId
     * @return mixed
     */
    public function show($dialogId)
    {
        $messages = Message::where('dialog_id',$dialogId)->get();
        return $messages;
    }
}
