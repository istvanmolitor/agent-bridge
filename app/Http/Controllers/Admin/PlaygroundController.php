<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromptRequest;
use App\Services\ClaudeCli;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlaygroundController extends Controller
{
    /**
     * Show the prompt playground page.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/Playground');
    }

    /**
     * Run a prompt through the claude CLI and return the raw JSON response.
     */
    public function run(PromptRequest $request, ClaudeCli $claudeCli): JsonResponse
    {
        return response()->json($claudeCli->run($request->validated('prompt')));
    }
}
