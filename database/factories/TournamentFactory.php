<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'matchplay_tournament_id' => $this->faker->unique()->randomNumber(6),
            'qr_code_uuid' => $this->faker->uuid(),
        ];
    }
}
