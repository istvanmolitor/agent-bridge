<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $apiKey = $token !== null
            ? ApiKey::query()
                ->with(['agentSession.agent', 'agentSession.workspace'])
                ->where('token', $token)
                ->first()
            : null;

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->setUserResolver(fn () => $apiKey->user);
        $request->attributes->set('apiKey', $apiKey);

        return $next($request);
    }
}
