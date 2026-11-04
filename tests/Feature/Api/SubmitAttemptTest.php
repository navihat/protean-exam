<?php

namespace Tests\Feature\Api;

use App\Enums\AttemptStatus;
use App\Exceptions\AttemptAlreadySubmittedException;
use App\Models\Exam;
use App\Models\Mongo\Attempt;
use App\Models\Option;
use App\Models\User;
use App\Services\Attempt\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\RefreshMongo;
use Tests\TestCase;

class SubmitAttemptTest extends TestCase
{
    use RefreshDatabase, RefreshMongo;

    private User $student;

    private Exam $exam;

    /** @var array<int, int> question_id => correct option id */
    private array $correct;

    /** @var array<int, int> question_id => a wrong option id */
    private array $wrong;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $this->exam = Exam::factory()->withQuestions(4)->create(['duration_minutes' => 15]);

        $options = Option::query()->whereIn('question_id', $this->exam->questions()->pluck('id'))->get();
        $this->correct = $options->where('is_correct', true)->pluck('id', 'question_id')->all();
        $this->wrong = $options->where('is_correct', false)->groupBy('question_id')->map->first()->map->id->all();

        Sanctum::actingAs($this->student);
    }

    private function start(): string
    {
        return $this->postJson(route('api.attempts.store', $this->exam))->assertCreated()->json('data.id');
    }

    /**
     * @return array<int, list<int>>
     */
    private function answers(int $correctCount): array
    {
        $answers = [];
        foreach (array_keys($this->correct) as $i => $questionId) {
            $answers[$questionId] = [$i < $correctCount ? $this->correct[$questionId] : $this->wrong[$questionId]];
        }

        return $answers;
    }

    public function test_submission_is_graded_on_the_server(): void
    {
        $id = $this->start();

        $this->postJson(route('api.attempts.submit', $id), [
            'answers' => $this->answers(3),
            // C5.1: anything besides option ids is ignored.
            'score' => 10,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.is_late', false)
            ->assertJsonPath('data.correct_count', 3)
            ->assertJsonPath('data.total_questions', 4)
            ->assertJsonPath('data.score', 7.5);

        $attempt = Attempt::find($id);
        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertSame(7.5, $attempt->score);
        $this->assertCount(4, $attempt->results);
    }

    public function test_late_submission_ignores_request_and_grades_saved_answers_only(): void
    {
        $id = $this->start();
        $first = array_key_first($this->correct);

        // What autosave (T20) would have stored before the deadline: one correct answer.
        Attempt::query()->whereKey($id)->update(['answers' => [$first => [$this->correct[$first]]]]);

        // 15 minutes plus a 30 second grace period, then one more second.
        $this->travel(15 * 60 + 31)->seconds();

        $this->postJson(route('api.attempts.submit', $id), ['answers' => $this->answers(4)])
            ->assertOk()
            ->assertJsonPath('data.is_late', true)
            ->assertJsonPath('data.correct_count', 1)
            ->assertJsonPath('data.score', 2.5);
    }

    public function test_submission_inside_grace_period_is_on_time(): void
    {
        $id = $this->start();

        $this->travel(15 * 60 + 20)->seconds();

        $this->postJson(route('api.attempts.submit', $id), ['answers' => $this->answers(4)])
            ->assertOk()
            ->assertJsonPath('data.is_late', false)
            ->assertJsonPath('data.score', 10);
    }

    public function test_submitting_twice_returns_409(): void
    {
        $id = $this->start();
        $this->postJson(route('api.attempts.submit', $id), ['answers' => $this->answers(4)])->assertOk();

        $this->postJson(route('api.attempts.submit', $id), ['answers' => $this->answers(0)])
            ->assertStatus(409)
            ->assertExactJson(['message' => 'This attempt has already been submitted.', 'code' => 'ATTEMPT_ALREADY_SUBMITTED']);

        $this->assertSame(10.0, Attempt::find($id)->score);
    }

    public function test_concurrent_submission_is_rejected_by_the_conditional_update(): void
    {
        $id = $this->start();
        // Loaded before the first submission, like a second request still in flight:
        // its status still says in_progress, so only the conditional update can stop it (C4.4).
        $stale = Attempt::find($id);

        $this->postJson(route('api.attempts.submit', $id), ['answers' => $this->answers(4)])->assertOk();

        try {
            app(AttemptService::class)->submit($stale, $this->answers(0));
            $this->fail('The second submission should have been rejected.');
        } catch (AttemptAlreadySubmittedException) {
            // expected
        }

        $this->assertSame(10.0, Attempt::find($id)->score);
    }

    public function test_other_student_cannot_submit(): void
    {
        $id = $this->start();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.attempts.submit', $id), ['answers' => $this->answers(4)])
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');

        $this->assertSame(AttemptStatus::InProgress, Attempt::find($id)->status);
    }

    public function test_invalid_answers_return_validation_error(): void
    {
        $id = $this->start();
        $first = array_key_first($this->correct);

        $this->postJson(route('api.attempts.submit', $id), ['answers' => [$first => ['abc']]])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonValidationErrors("answers.{$first}.0");
    }
}
