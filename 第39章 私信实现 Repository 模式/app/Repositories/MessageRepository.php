<?php
namespace App\Repositories;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;

/**
 * Class MessageRepository
 * @package App\Repositories
 */
class MessageRepository
{
    /**
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes)
    {
        return Message::create($attributes);
    }

    /**
     * @return mixed
     */
    public function getAllMessages()
    {
        return $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser' => function ($query){
                return $query->select(['id','name','avatar']);
            },'toUser' => function ($query){
                return $query->select(['id','name','avatar']);
            }])->latest()->get();
    }

    /**
     * @param $dialogId
     * @return mixed
     */
    public function getDialogMessagesBy($dialogId)
    {
        return Message::where('dialog_id',$dialogId)->with(['fromUser' => function ($query){
            return $query->select(['id','name','avatar']);
        },'toUser' => function ($query){
            return $query->select(['id','name','avatar']);
        }])->latest()->get();
    }

    /**
     * @param $dialogId
     * @return mixed
     */
    public function getStingleMessageBy($dialogId)
    {
        return Message::where('dialog_id',$dialogId)->first();
    }
}