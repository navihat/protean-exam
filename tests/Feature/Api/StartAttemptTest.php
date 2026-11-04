<?php

namespace Tests\Feature\Api;

use App\Models\Exam;
use App\Models\Mongo\ActivityLog;
use App\Models\Mongo\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\RefreshMongo;
use Tests\TestCase;

class StartAttemptTest extends TestCase
{
    use RefreshDatabase, RefreshMongo;

    public function test_student_starts_an_attempt_without_seeing_correct_answers(): void
    {
        $student = User::factory()->create();
        $exam = Exam::factory()->withQuestions(3)->create(['duration_minutes' => 15]);
        Sanctum::actingAs($student);
        $this->freezeSecond();

        $response = $this->postJson(route('api.attempts.store', $exam))
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.exam.id', $exam->id)
            ->assertJsonPath('data.remaining_seconds', 15 * 60)
            ->assertJsonCount(3, 'data.questions')
            ->assertJsonCount(4, 'data.questions.0.options');

        // C5.2: no is_correct anywhere in the response before submission.
        $this->assertStringNotContainsString('is_correct', $response->getContent());

        // The snapshot in Mongo keeps is_correct for grading.
        $attempt = Attempt::find($response->json('data.id'));
        $this->assertSame($student->id, $attempt->user_id);
        $this->assertCount(1, array_filter($attempt->snapshot[0]['options'], fn (array $o) => $o['is_correct']));
        $this->assertEquals($attempt->started_at->copy()->addMinutes(15), $attempt->expires_at);
    }

    public function test_starting_again_while_open_returns_the_same_attempt(): void
    {
        $exam = Exam::factory()->withQuestions(2)->create();
        Sanctum::actingAs(User::factory()->create());

        $first = $this->postJson(route('api.attempts.store', $exam))->assertCreated()->json('data.id');

        $this->postJson(route('api.attempts.store', $exam))
            ->assertOk()
            ->assertJsonPath('data.id', $first);

        $this->assertSame(1, Attempt::count());
    }

    public function test_each_new_attempt_gets_its_own_order(): void
    {
        // 8 questions x 4 options: the chance of two identical orders is about 1 in 10^15.
        $exam = Exam::factory()->withQuestions(8)->create();
        Sanctum::actingAs(User::factory()->create());

        $first = $this->postJson(route('api.attempts.store', $exam))->assertCreated();
        $this->postJson(route('api.attempts.submit', $first->json('data.id')))->assertOk();
        $second = $this->postJson(route('api.attempts.store', $exam))->assertCreated();

        $order = fn ($response) => collect($response->json('data.questions'))
            ->map(fn (array $q) => [$q['question_id'], array_column($q['options'], 'option_id')])
            ->all();

        $this->assertNotSame($first->json('data.id'), $second->json('data.id'));
        $this->assertNotSame($order($first), $order($second));
    }

    public function test_draft_or_empty_exam_cannot_be_started(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach ([Exam::factory()->draft()->withQuestions(2)->create(), Exam::factory()->create()] as $exam) {
            $this->postJson(route('api.attempts.store', $exam))
                ->assertUnprocessable()
                ->assertJsonPath('code', 'EXAM_NOT_AVAILABLE');
        }

        $this->assertSame(0, Attempt::count());
    }

    public function test_only_the_owner_can_read_an_attempt(): void
    {
        $exam = Exam::factory()->withQuestions(2)->create();
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson(route('api.attempts.store', $exam))->json('data.id');

        $shown = $this->getJson(route('api.attempts.show', $id))->assertOk()->assertJsonPath('data.id', $id);
        $this->assertStringNotContainsString('is_correct', $shown->getContent());

        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('api.attempts.show', $id))
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_unknown_attempt_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.attempts.show', '000000000000000000000000'))
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_starting_is_recorded_in_activity_log(): void
    {
        $student = User::factory()->create();
        $exam = Exam::factory()->withQuestions(1)->create();
        Sanctum::actingAs($student);

        $id = $this->postJson(route('api.attempts.store', $exam))->json('data.id');

        $log = ActivityLog::where('action', 'attempt.started')->sole();
        $this->assertSame($student->id, $log->user_id);
        $this->assertSame('exam', $log->subject_type);
        $this->assertSame($exam->id, $log->subject_id);
        $this->assertSame($id, $log->meta['attempt_id']);
    }
}
