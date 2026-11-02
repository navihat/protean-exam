<?php

namespace Tests\Feature\Admin;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->exam = Exam::factory()->create();
    }

    public function test_admin_can_add_a_question_with_options(): void
    {
        $this->actingAs($this->admin)->post(route('admin.exams.questions.store', $this->exam), [
            'content' => '2 + 2 = ?',
            'options' => [
                ['content' => '3', 'is_correct' => '0'],
                ['content' => '4', 'is_correct' => '1'],
                ['content' => '5', 'is_correct' => '0'],
            ],
        ])->assertRedirect(route('admin.exams.show', $this->exam));

        $question = $this->exam->questions()->with('options')->sole();
        $this->assertSame('2 + 2 = ?', $question->content);
        $this->assertSame(1, $question->position);
        $this->assertSame(['3', '4', '5'], $question->options->pluck('content')->all());
        $this->assertSame([false, true, false], $question->options->pluck('is_correct')->all());
    }

    public function test_question_requires_at_least_one_correct_option(): void
    {
        $this->actingAs($this->admin)->post(route('admin.exams.questions.store', $this->exam), [
            'content' => 'No correct answer',
            'options' => [
                ['content' => 'A', 'is_correct' => '0'],
                ['content' => 'B', 'is_correct' => '0'],
            ],
        ])->assertSessionHasErrors('options');

        $this->assertSame(0, Question::count());
    }

    public function test_question_requires_at_least_two_options_with_content(): void
    {
        $this->actingAs($this->admin)->post(route('admin.exams.questions.store', $this->exam), [
            'content' => 'Too few',
            'options' => [['content' => 'Only one', 'is_correct' => '1']],
        ])->assertSessionHasErrors('options');

        $this->actingAs($this->admin)->post(route('admin.exams.questions.store', $this->exam), [
            'content' => 'Blank option',
            'options' => [['content' => 'A', 'is_correct' => '1'], ['content' => '', 'is_correct' => '0']],
        ])->assertSessionHasErrors('options.1.content');

        $this->assertSame(0, Question::count());
    }

    public function test_admin_can_update_a_question_and_replace_its_options(): void
    {
        $question = Question::factory()->for($this->exam)->withOptions(4)->create();

        $this->actingAs($this->admin)->put(route('admin.exams.questions.update', [$this->exam, $question]), [
            'content' => 'Updated?',
            'options' => [
                ['content' => 'Yes', 'is_correct' => '1'],
                ['content' => 'No', 'is_correct' => '1'],
            ],
        ])->assertRedirect(route('admin.exams.show', $this->exam));

        $question->refresh()->load('options');
        $this->assertSame('Updated?', $question->content);
        $this->assertSame(['Yes', 'No'], $question->options->pluck('content')->all());
        $this->assertSame([true, true], $question->options->pluck('is_correct')->all());
    }

    public function test_admin_can_delete_a_question(): void
    {
        $question = Question::factory()->for($this->exam)->withOptions()->create();

        $this->actingAs($this->admin)->delete(route('admin.exams.questions.destroy', [$this->exam, $question]))
            ->assertRedirect(route('admin.exams.show', $this->exam));

        $this->assertModelMissing($question);
    }

    public function test_question_forms_render(): void
    {
        $question = Question::factory()->for($this->exam)->withOptions()->create();

        $this->actingAs($this->admin)->get(route('admin.exams.questions.create', $this->exam))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.exams.questions.edit', [$this->exam, $question]))
            ->assertOk()
            ->assertSee($question->content);
    }

    public function test_question_routes_are_scoped_to_their_exam(): void
    {
        $otherExam = Exam::factory()->create();
        $question = Question::factory()->for($otherExam)->withOptions()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.exams.questions.edit', [$this->exam, $question]))
            ->assertNotFound();
        $this->actingAs($this->admin)
            ->delete(route('admin.exams.questions.destroy', [$this->exam, $question]))
            ->assertNotFound();

        $this->assertModelExists($question);
    }

    public function test_questions_of_a_deleted_exam_are_not_reachable(): void
    {
        $question = Question::factory()->for($this->exam)->withOptions()->create();
        $this->exam->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.exams.questions.edit', [$this->exam, $question]))
            ->assertNotFound();
    }

    public function test_student_cannot_manage_questions(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('admin.exams.questions.store', $this->exam), [
            'content' => 'x',
            'options' => [['content' => 'a', 'is_correct' => '1'], ['content' => 'b']],
        ])->assertForbidden();

        $this->assertSame(0, Question::count());
    }
}
