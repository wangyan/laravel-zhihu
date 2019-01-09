<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Class SettingController
 * @package App\Http\Controllers
 */
class SettingController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('users.setting');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $settings = array_merge(user()->settings,array_only($request->all(),['city','bio']));
        user()->update(['settings' => $settings]);
        return back();
    }
}
