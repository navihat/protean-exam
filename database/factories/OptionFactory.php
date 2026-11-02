<?php

namespace Database\Factories;

use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => fake()->words(2, true),
            'is_correct' => false,
            'position' => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => ['is_correct' => true]);
    }
}
