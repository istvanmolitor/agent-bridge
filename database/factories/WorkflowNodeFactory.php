<?php

namespace Database\Factories;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowNode>
 */
class WorkflowNodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'type' => fake()->randomElement(['start', 'agent', 'http_request']),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'position_x' => fake()->randomFloat(2, 0, 800),
            'position_y' => fake()->randomFloat(2, 0, 600),
        ];
    }
}
