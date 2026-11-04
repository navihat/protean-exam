<?php

namespace Tests\Feature\Api;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\RefreshMongo;
use Tests\TestCase;

class ExamApiTest extends TestCase
{
    use RefreshDatabase, RefreshMongo;

    public function test_lists_published_exams_with_question_count(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $published = Exam::factory()->withQuestions(2)->create();
        Exam::factory()->draft()->create();

        $this->getJson(route('api.exams.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.questions_count', 2)
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'total']]);
    }

    public function test_invalid_page_returns_validation_error_format(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.exams.index', ['page' => 0]))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonValidationErrors('page');
    }

    public function test_empty_page_parameter_means_first_page(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Exam::factory()->create();

        $this->getJson(route('api.exams.index', ['q' => '', 'page' => '']))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_gets_401_with_error_code(): void
    {
        $this->getJson(route('api.exams.index'))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.', 'code' => 'UNAUTHENTICATED']);
    }
}
