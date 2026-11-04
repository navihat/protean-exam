<?php

namespace Tests\Feature\Api;

use App\Models\Exam;
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
}
