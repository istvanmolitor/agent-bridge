<?php

namespace Database\Factories;

use App\Models\AgentSession;
use App\Models\PromptLog;
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
            'agent_session_id' => AgentSession::factory(),
            'prompt' => fake()->sentence(),
            'response' => fake()->paragraph(),
            'success' => true,
            'error' => null,
        ];
    }
}
