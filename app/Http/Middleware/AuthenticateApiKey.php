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
        $plainTextToken = $request->bearerToken();

        $apiKey = $plainTextToken !== null
            ? ApiKey::query()->where('token', ApiKey::hashToken($plainTextToken))->first()
            : null;

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->setUserResolver(fn () => $apiKey->user);

        return $next($request);
    }
}
