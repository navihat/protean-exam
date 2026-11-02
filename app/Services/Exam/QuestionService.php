<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /**
     * @param  array{content: string, options: list<array{content: string, is_correct: bool}>}  $data
     */
    public function create(Exam $exam, array $data): Question
    {
        return DB::transaction(function () use ($exam, $data) {
            $question = $exam->questions()->create([
                'content' => $data['content'],
                'position' => (int) $exam->questions()->max('position') + 1,
            ]);

            $this->storeOptions($question, $data['options']);

            return $question;
        });
    }

    /**
     * Options are replaced as a whole: simpler than diffing, and attempts never
     * depend on option ids because they grade against their own snapshot (D5).
     *
     * @param  array{content: string, options: list<array{content: string, is_correct: bool}>}  $data
     */
    public function update(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data) {
            $question->update(['content' => $data['content']]);
            $question->options()->delete();
            $this->storeOptions($question, $data['options']);

            return $question;
        });
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    /**
     * @param  list<array{content: string, is_correct: bool}>  $options
     */
    private function storeOptions(Question $question, array $options): void
    {
        $question->options()->createMany(
            collect($options)->values()->map(fn (array $option, int $index) => [
                'content' => $option['content'],
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'position' => $index + 1,
            ])->all()
        );
    }
}
