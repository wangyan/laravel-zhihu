<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Class UsersController
 * @package App\Http\Controllers
 */
class UsersController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function avatar()
    {
        return view('users.avatar');
    }
}
