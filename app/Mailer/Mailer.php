<?php
namespace App\Mailer;


use Illuminate\Support\Facades\Mail;
use Naux\Mail\SendCloudTemplate;

class Mailer
{
    /**
     * @param $template
     * @param $email
     * @param array $data
     */
    public function sendTo($template, $email, array $data)
    {
        $content = new SendCloudTemplate($template,$data);

        Mail::raw($content,  function ($message) use ($email) {
            $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
            $message->to($email);
        });
    }
}