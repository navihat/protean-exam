<?php

namespace App\Services\Attempt;

use App\Enums\AttemptStatus;
use App\Exceptions\ExamNotAvailableException;
use App\Models\Exam;
use App\Models\Mongo\Attempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;

class AttemptService
{
    /**
     * Starts a new attempt with its own shuffled snapshot (T11).
     * The new attempt has wasRecentlyCreated = true, which makes the API answer 201.
     */
    public function start(User $user, Exam $exam): Attempt
    {
        if (! $exam->is_published) {
            throw new ExamNotAvailableException;
        }

        $exam->load('questions.options');

        if ($exam->questions->isEmpty()) {
            throw new ExamNotAvailableException;
        }

        $now = now();

        $attempt = Attempt::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'status' => AttemptStatus::InProgress,
            'exam' => ['name' => $exam->name, 'duration_minutes' => $exam->duration_minutes],
            'snapshot' => $this->snapshot($exam),
            'started_at' => $now,
            'expires_at' => $now->copy()->addMinutes($exam->duration_minutes),
        ]);

        return $attempt;
    }

    /**
     * Questions and options in a fresh random order, with is_correct kept for grading (D5).
     *
     * @return list<array{question_id: int, content: string, options: list<array{option_id: int, content: string, is_correct: bool}>}>
     */
    private function snapshot(Exam $exam): array
    {
        return $exam->questions->shuffle()->map(fn (Question $question) => [
            'question_id' => $question->id,
            'content' => $question->content,
            'options' => $question->options->shuffle()->map(fn (Option $option) => [
                'option_id' => $option->id,
                'content' => $option->content,
                'is_correct' => $option->is_correct,
            ])->values()->all(),
        ])->values()->all();
    }
}
