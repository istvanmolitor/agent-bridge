<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\PromptLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromptLog>
 */
class PromptLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'api_key_id' => ApiKey::factory(),
            'prompt' => fake()->sentence(),
            'response' => fake()->paragraph(),
            'success' => true,
            'error' => null,
            'session_id' => fake()->uuid(),
        ];
    }
}
