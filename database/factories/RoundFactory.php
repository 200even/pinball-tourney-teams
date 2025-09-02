<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Round>
 */
class RoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tournament_id' => \App\Models\Tournament::factory(),
            'matchplay_round_id' => $this->faker->unique()->randomNumber(6),
            'round_number' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->words(2, true),
            'status' => $this->faker->randomElement(['pending', 'active', 'completed']),
            'completed_at' => null,
            'matchplay_data' => [],
        ];
    }
}
