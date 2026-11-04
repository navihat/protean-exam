<?php

namespace App\Observers;

use App\Events\ContentChanged;
use App\Models\Exam;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Side effects of an exam change (T15): record the activity.
 * ShouldHandleEventsAfterCommit defers this until the surrounding transaction
 * commits, so a rollback leaves no log entry (C6.2).
 */
class ExamObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Exam $exam): void
    {
        $this->changed('exam.created', $exam, ['name' => $exam->name]);
    }

    public function updated(Exam $exam): void
    {
        $fields = array_keys($exam->getChanges());

        $this->changed('exam.updated', $exam, [
            'name' => $exam->name,
            'fields' => array_values(array_diff($fields, ['updated_at'])),
        ]);
    }

    /**
     * Fires for the soft delete as well (D11).
     */
    public function deleted(Exam $exam): void
    {
        $this->changed('exam.deleted', $exam, ['name' => $exam->name]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function changed(string $action, Exam $exam, array $meta): void
    {
        ContentChanged::dispatch($action, 'exam', $exam->id, $meta);
    }
}
