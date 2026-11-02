<?php

namespace Tests\Feature\Database;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_question_cascades_to_its_options(): void
    {
        $exam = Exam::factory()->withQuestions(2)->create();
        $question = $exam->questions()->first();

        $question->delete();

        $this->assertSame(0, Option::where('question_id', $question->id)->count());
        $this->assertSame(4, Option::count());
    }

    public function test_deleting_an_exam_is_soft_and_keeps_its_questions(): void
    {
        $exam = Exam::factory()->withQuestions(2)->create();

        $exam->delete();

        $this->assertSoftDeleted($exam);
        $this->assertSame(2, Question::where('exam_id', $exam->id)->count());
    }

    public function test_search_scope_filters_by_name(): void
    {
        Exam::factory()->create(['name' => 'Laravel căn bản']);
        Exam::factory()->create(['name' => 'PHP nâng cao']);

        $this->assertSame(['Laravel căn bản'], Exam::search('laravel')->pluck('name')->all());
        $this->assertSame(2, Exam::search(null)->count());
    }
}
