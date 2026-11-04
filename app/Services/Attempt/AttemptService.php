<?php

namespace App\Services\Attempt;

use App\Enums\AttemptStatus;
use App\Events\AttemptStarted;
use App\Events\AttemptSubmitted;
use App\Exceptions\AttemptAlreadySubmittedException;
use App\Exceptions\ExamNotAvailableException;
use App\Models\Exam;
use App\Models\Mongo\Attempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Arr;

class AttemptService
{
    public function __construct(private readonly GradingService $grading) {}

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

        if ($open !== null) {
            // Time is up but it was never submitted: close it with the saved answers (D7).
            $this->close($open, []);
        }

        return $this->start($user, $exam);
    }

    /**
     * @param  array<int, list<int>>  $answers  question_id => option ids, from the request
     */
    public function submit(Attempt $attempt, array $answers): Attempt
    {
        if ($attempt->status === AttemptStatus::Submitted || ! $this->close($attempt, $answers)) {
            throw new AttemptAlreadySubmittedException;
        }

        return $attempt;
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

    /**
     * Grades the attempt and marks it submitted in one conditional update (C4.4).
     * Returns false when another request closed it first.
     *
     * @param  array<int, list<int>>  $requestAnswers
     */
    private function close(Attempt $attempt, array $requestAnswers): bool
    {
        $now = now();
        $isLate = $now->greaterThan($attempt->expires_at->copy()->addSeconds(config('exam.submit_grace_seconds')));
        $saved = $attempt->answers ?? [];

        // Late: the request is ignored, only answers saved before the deadline count (D7).
        $answers = $isLate ? $saved : array_replace($saved, array_map(fn (array $ids) => array_map('intval', $ids), $requestAnswers));
        $answers = Arr::only($answers, array_column($attempt->snapshot, 'question_id'));

        $grade = $this->grading->grade($attempt->snapshot, $answers);

        $updated = Attempt::query()
            ->whereKey($attempt->getKey())
            ->where('status', AttemptStatus::InProgress->value)
            ->update([
                'status' => AttemptStatus::Submitted->value,
                'answers' => $answers,
                'results' => $grade['results'],
                'correct_count' => $grade['correct_count'],
                'total_questions' => $grade['total_questions'],
                'score' => $grade['score'],
                'is_late' => $isLate,
                'submitted_at' => $now,
            ]);

        if ($updated === 0) {
            return false;
        }

        $attempt->refresh();

        AttemptSubmitted::dispatch($attempt);

        return true;
    }
}
