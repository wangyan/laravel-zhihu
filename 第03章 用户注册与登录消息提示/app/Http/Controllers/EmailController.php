<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    /**
     * @param $token
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    function verify($token)
    {
        $user = User::where('confirmation_token',$token)->first();

        if(is_null($user)){
            flash()->overlay('邮箱验证失败！', '温馨提示');
            return redirect('/');
        }

        $user->is_active = 1;

        $user->confirmation_token= str_random(40);
        $user->save();

        flash('邮箱验证成功！')->success()->important();
        return redirect('/home');
    }
}
