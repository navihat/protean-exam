<?php

namespace App\Services\Attempt;

use App\Enums\AttemptStatus;
use App\Events\AttemptStarted;
use App\Exceptions\ExamNotAvailableException;
use App\Models\Exam;
use App\Models\Mongo\Attempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;

class AttemptService
{
    /**
     * Returns the student's open attempt for this exam, or starts a new one (T11, D8).
     * A new attempt has wasRecentlyCreated = true, which makes the API answer 201 instead of 200.
     */
    public function startOrResume(User $user, Exam $exam): Attempt
    {
        $open = Attempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', AttemptStatus::InProgress->value)
            ->first();

        if ($open !== null && $open->expires_at->isFuture()) {
            return $open;
        }

        return $this->start($user, $exam);
    }

    private function start(User $user, Exam $exam): Attempt
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

        AttemptStarted::dispatch($attempt);

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
