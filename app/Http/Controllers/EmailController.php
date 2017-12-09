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
            return redirect('/');
        }

        $user->is_active = 1;

        $user->confirmation_token= str_random(40);
        $user->save();

        return redirect('/home');
    }
}
