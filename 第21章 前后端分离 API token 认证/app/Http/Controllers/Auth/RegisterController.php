<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user =  User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => 'default.jpg',
            'confirmation_token' => str_random(40),
            'password' => bcrypt($data['password']),
            'api_token' => str_random(60),
        ]);

        $this->sendVerifyEmailTo($user);
        return $user;
    }

    /**
     * @param $user
     */
    private function sendVerifyEmailTo($user)
    {
        $data = [
            'name' => $user->name,
            'url'  => Route('email.verify',['token' => $user->confirmation_token])
        ];

        Mail::send('emails.register', $data, function ($message) use ($user) {
            $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
            $message->to($user->email);
            $message->subject('请验证您的 Email 地址');
        });
    }
}
