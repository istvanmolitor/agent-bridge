<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentSession>
 */
class AgentSessionFactory extends Factory
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
            'agent_id' => Agent::factory(),
            'workspace_id' => null,
            'session_id' => fake()->uuid(),
            'last_used_at' => now(),
        ];
    }

    /**
     * A fresh session that has not started a conversation yet.
     */
    public function fresh(): static
    {
        return $this->state(fn () => [
            'session_id' => null,
            'last_used_at' => null,
        ]);
    }
}
