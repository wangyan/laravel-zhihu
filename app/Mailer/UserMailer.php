<?php

namespace App\Mailer;

use Auth;

class UserMailer extends Mailer
{
    /**
     * @param $email
     * @param $name
     */
    public function followNotifyEmail($email, $name)
    {
        $data = [
            'yourName' => $name,
            'followerName' => Auth::guard('api')->user()->name,
            'url'  => 'http://zhihu.dev/user/'.Auth::guard('api')->user()->id,
        ];

        $this->sendTo('zhihu_new_user_follow',$email,$data);
    }

    public function passwordReset($name,$email,$token)
    {
        $data = [
            'title' => env('APP_NAME','Laravel'),
            'name'  => $name,
            'url'   => url('password/reset',$token)
        ];

        $this->sendTo('zhihu_password_reset',$email,$data);
    }

    public function verifyEmail($name,$email,$confirmation_token)
    {
        $data = [
            'name' => $name,
            'url'  => Route('email.verify',['token' => $confirmation_token])
        ];

        $this->sendTo('zhihu_user_register',$email,$data);
    }
}