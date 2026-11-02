<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => rtrim(fake()->sentence(), '.').'?',
            'position' => 0,
        ];
    }

    /**
     * Attach $count options; the first $correct of them are correct.
     */
    public function withOptions(int $count = 4, int $correct = 1): static
    {
        return $this->afterCreating(function (Question $question) use ($count, $correct) {
            $question->options()->createMany(
                collect(range(1, $count))->map(fn (int $i) => [
                    'content' => "Option {$i}",
                    'is_correct' => $i <= $correct,
                    'position' => $i,
                ])->all()
            );
        });
    }
}
