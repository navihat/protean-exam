<?php

namespace App\Listeners;

use App\Events\AttemptStarted;
use App\Events\ContentChanged;
use App\Models\Mongo\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;

/**
 * The only place that writes activity_logs (C6.1, D10).
 *
 * Laravel discovers this listener automatically: every public `handle*` method is
 * registered for the event class in its type hint. It runs synchronously, so the
 * current request is still available for the user, IP and user agent.
 */
class RecordActivity
{
    public function __construct(private readonly Request $request) {}

    public function handleContentChanged(ContentChanged $event): void
    {
        $this->record($event->action, $this->request->user()?->getAuthIdentifier(), $event->subjectType, $event->subjectId, $event->meta);
    }

    /**
     * subject_id stays a MySQL id (the exam, C4.2); the Mongo attempt id goes into meta.
     * The user comes from the attempt, not the request, because an expired attempt can be
     * closed while its owner starts the exam again.
     */
    public function handleAttemptStarted(AttemptStarted $event): void
    {
        $attempt = $event->attempt;

        $this->record('attempt.started', $attempt->user_id, 'exam', $attempt->exam_id, ['attempt_id' => $attempt->id]);
    }

    public function handleLogin(Login $event): void
    {
        $userId = $event->user->getAuthIdentifier();

        $this->record('auth.login', $userId, 'user', $userId, ['guard' => $event->guard]);
    }

    public function handleLogout(Logout $event): void
    {
        $userId = $event->user?->getAuthIdentifier();

        $this->record('auth.logout', $userId, 'user', $userId, ['guard' => $event->guard]);
    }

    public function handleRegistered(Registered $event): void
    {
        $userId = $event->user->getAuthIdentifier();

        $this->record('auth.registered', $userId, 'user', $userId);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function record(string $action, int|string|null $userId, string $subjectType, int|string|null $subjectId, array $meta = []): void
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta,
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
