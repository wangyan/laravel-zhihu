<?php

namespace App\Http\Controllers;

use App\Repositories\QuestionsRepository;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreQuestionRequest;

class QuestionsController extends Controller
{
    protected $questionRepository;


    /**
     * QuestionsController constructor.
     * @param QuestionsRepository $questionRepository
     */
    public function __construct(QuestionsRepository $questionRepository)
    {
        $this->questionRepository = $questionRepository;
        $this->middleware('auth')->except(['index','show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $questions = $this->questionRepository->getQuestionsFeed();
        return view('questions.index',compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('questions.create');
    }


    /**
     * @param StoreQuestionRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function store(StoreQuestionRequest $request)
    {
        $topics = $request->get('topic');
        if(is_array($topics)){
            $topicsArray = $this->normalizeTopic($topics);
        }
        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'user_id' => Auth::id()
        ];
        $question = Question::create($data);
        Auth::user()->increment('questions_count');
        $question->topics()->attach($topicsArray);
        return view('questions.show',compact('question'));
    }

    /**
     * @param array $topics
     * @return array
     */
    private function normalizeTopic(array $topics)
    {
        return collect($topics)->map(function($topic){
            if(is_numeric($topic)){
                Topic::find($topic)->increment('questions_count');
                return (int) $topic;
            }
            $newTopic = Topic::create(['name'=>$topic,'questions_count'=>1]);
            return $newTopic->id;
        })->toArray();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $question = $this->questionRepository->byIdWithTopicsAndAnswers($id);
        return view('questions.show',compact('question'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $question = $this->questionRepository->byID($id);
        if (Auth::user()->owns($question)){
            return view('questions.edit',compact('question'));
        }
        return back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreQuestionRequest $request, $id)
    {

        $question = $this->questionRepository->byID($id);
        $question->update([
            'title' => $request->get('title'),
            'body' => $request->get('body'),
        ]);
        $topics = $request->get('topic');
        if(is_array($topics)){
            $topicsArray = $this->normalizeTopic($topics);
        }
        $question->topics()->sync($topicsArray);
        return redirect()->route('questions.show',$question->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $question = $this->questionRepository->byID($id);
        if (Auth::user()->owns($question)){
            $question->delete();
            Auth::user()->decrement('questions_count');
            return redirect('/questions/');
        }
        abort(403,'Forbidden');
    }
}
