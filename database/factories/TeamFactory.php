<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
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
            'player1_id' => \App\Models\Player::factory(),
            'player2_id' => \App\Models\Player::factory(),
            'name' => $this->faker->words(2, true),
            'generated_name' => $this->faker->words(3, true),
            'total_points' => 0,
            'games_played' => 0,
            'position' => null,
        ];
    }
}
