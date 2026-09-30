<?php

namespace App\Http\Controllers;

use App\Services\Tutorial;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The in-app guide to using the ERP. Topics live in resources/views/erp/tutorial/topics. */
class TutorialController extends Controller
{
    public function index(Request $request): View
    {
        return view('erp.tutorial.index', ['groups' => Tutorial::groupedFor($request->user())]);
    }

    public function show(Request $request, string $topic): View
    {
        abort_unless(Tutorial::canView($topic, $request->user()), 403);

        return view('erp.tutorial.show', [
            'slug' => $topic,
            'topic' => Tutorial::TOPICS[$topic],
            'groups' => Tutorial::groupedFor($request->user()),
        ]);
    }
}
