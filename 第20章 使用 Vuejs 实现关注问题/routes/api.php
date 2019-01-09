<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/topics', function (Request $request) {
    $topics = App\Models\Topic::select(['id','name'])->where('name','like','%'.$request->query('q').'%')->get();
    return $topics;
});

Route::post('/question/follower',function (Request $request){
    $followed = \App\Models\Follow::where('question_id',$request->get('question'))
        ->where('user_id',$request->get('user'))
        ->count();
    if ($followed) {
        return response()->json(['followed' => true]);
    }
    return response()->json(['followed' => false]);

})->middleware('api');

Route::post('/question/follow',function (Request $request){
    $question_id =  $request->get('question');
    $followed = \App\Models\Follow::where('question_id',$question_id)
        ->where('user_id',$request->get('user'))
        ->first();
    if ($followed !== null) {
        $followed->delete();
        \App\Models\Question::find($question_id)->decrement('followers_count');
        return response()->json(['followed' => false]);
    }
    \App\Models\Follow::create([
        'question_id'=>$request->get('question'),
        'user_id'=>$request->get('user'),
    ]);
    \App\Models\Question::find($question_id)->increment('followers_count');
    return response()->json(['followed' => true]);

})->middleware('api');