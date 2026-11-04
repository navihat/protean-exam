<?php

namespace App\Observers;

use App\Events\ContentChanged;
use App\Models\Question;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Same as ExamObserver for questions (T15).
 */
class QuestionObserver implements ShouldHandleEventsAfterCommit
{
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
        ContentChanged::dispatch($action, 'question', $question->id, ['exam_id' => $question->exam_id]);
    }
}
