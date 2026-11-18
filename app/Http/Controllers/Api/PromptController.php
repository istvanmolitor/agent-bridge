<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromptRequest;
use App\Services\ClaudeCli;
use Illuminate\Http\JsonResponse;

class PromptController extends Controller
{
    public function store(PromptRequest $request, ClaudeCli $claudeCli): JsonResponse
    {
        $response = $claudeCli->run($request->validated('prompt'));

        return response()->json($response, $response['success'] ? 200 : 502);
    }
}
