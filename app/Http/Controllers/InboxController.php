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
        return view('inbox.index',['messages' => $messages->unique('dialog_id')->groupBy('to_user_id')]);
    }

    /**
     * @param $dialogId
     * @return mixed
     */
    public function show($dialogId)
    {
        $messages = Message::where('dialog_id',$dialogId)->latest()->get();
        return view('inbox.show',compact('messages','dialogId'));
    }


    /**
     * @param $dialogId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store($dialogId)
    {
        $message = Message::where('dialog_id',$dialogId)->first();
        $toUserId = $message->from_user_id === Auth::id() ? $message->to_user_id : $message->from_user_id;
        Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $toUserId,
            'body' => request('body'),
            'dialog_id' => $dialogId
        ]);
        return back();
    }
}
