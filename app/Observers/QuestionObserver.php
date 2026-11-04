<?php

namespace App\Observers;

use App\Events\ContentChanged;
use App\Models\Question;
use App\Services\Exam\ExamService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Same as ExamObserver for questions (T15, T19). The cached exam list shows
 * `questions_count`, so a question change invalidates it too.
 */
class QuestionObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly ExamService $exams) {}

    public function created(Question $question): void
    {
        $this->changed('question.created', $question);
    }

    /**
     * `saved` instead of `updated`: editing only the options leaves the question row
     * unchanged, and Eloquent fires `updated` only when a column actually changed.
     * `saved` fires on every save() call, so skip the one that belongs to the create.
     */
    public function saved(Question $question): void
    {
        if (! $question->wasRecentlyCreated) {
            $this->changed('question.updated', $question);
        }
    }

    public function deleted(Question $question): void
    {
        $this->changed('question.deleted', $question);
    }

    private function changed(string $action, Question $question): void
    {
        $this->exams->flushPublishedListCache();

        ContentChanged::dispatch($action, 'question', $question->id, ['exam_id' => $question->exam_id]);
    }
}
