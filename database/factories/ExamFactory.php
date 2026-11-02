<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => rtrim(fake()->unique()->sentence(3), '.'),
            'description' => fake()->sentence(),
            'duration_minutes' => 15,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }

    /**
     * Exam with $count questions, each having 4 options of which the first is correct.
     */
    public function withQuestions(int $count = 3): static
    {
        return $this->has(Question::factory()->count($count)->withOptions(), 'questions');
    }
}
