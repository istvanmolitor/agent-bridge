<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptLibraryController extends Controller
{
    /**
     * Fetch a stored prompt by its name, scoped to the API key's owner.
     */
    public function show(Request $request, string $name): JsonResponse
    {
        /** @var Prompt $prompt */
        $prompt = $request->user()->prompts()
            ->where('name', $name)
            ->firstOrFail(['id', 'name', 'content', 'created_at', 'updated_at']);

        return response()->json([
            'id' => $prompt->id,
            'name' => $prompt->name,
            'content' => $prompt->content,
            'created_at' => $prompt->created_at,
            'updated_at' => $prompt->updated_at,
        ]);
    }
}
