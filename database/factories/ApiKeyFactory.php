<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainTextToken = ApiKey::generatePlainTextToken();

        return [
            'user_id' => User::factory(),
            'agent_id' => Agent::factory(),
            'name' => fake()->words(2, true),
            'token' => ApiKey::hashToken($plainTextToken),
            'plain_token' => $plainTextToken,
            'claude_session_id' => null,
            'last_used_at' => null,
        ];
    }
}
