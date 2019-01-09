<?php

namespace App\Http\Controllers;

use App\Repositories\TopicRepository;
use Illuminate\Http\Request;

/**
 * Class TopicsController
 * @package App\Http\Controllers
 */
class TopicsController extends Controller
{
    /**
     * @var TopicRepository
     */
    protected $topics;

    /**
     * TopicsController constructor.
     * @param $topics
     */
    public function __construct(TopicRepository $topics)
    {
        $this->topics = $topics;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        return $this->topics->getTopicsForTagging($request);
    }
}
