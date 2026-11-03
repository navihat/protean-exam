<?php

namespace Tests\Feature\Exam;

use App\Models\Exam;
use App\Services\Exam\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RefreshMongo;
use Tests\TestCase;

class PublishedExamListCacheTest extends TestCase
{
    use RefreshDatabase, RefreshMongo;

    private ExamService $exams;

    protected function setUp(): void
    {
        parent::setUp();

        // Same store as the real .env, so the cached paginator really goes through serialize().
        config(['cache.default' => 'database']);
        $this->exams = app(ExamService::class);
    }

    public function test_lists_only_published_exams_with_question_count(): void
    {
        $published = Exam::factory()->withQuestions(3)->create(['is_published' => true]);
        Exam::factory()->create(['is_published' => false]);

        $page = $this->exams->paginatePublished(null);

        $this->assertSame([$published->id], $page->pluck('id')->all());
        $this->assertSame(3, $page->first()->questions_count);
    }

    public function test_second_call_is_served_from_cache(): void
    {
        $exam = Exam::factory()->create(['name' => 'Tên cũ', 'is_published' => true]);
        $this->exams->paginatePublished(null);

        // Query builder bypasses model events, so nothing invalidates the cache.
        DB::table('exams')->where('id', $exam->id)->update(['name' => 'Đổi lén']);

        $this->assertSame('Tên cũ', $this->exams->paginatePublished(null)->first()->name);
    }

    public function test_updating_an_exam_invalidates_the_cache(): void
    {
        $exam = Exam::factory()->create(['name' => 'Tên cũ', 'is_published' => true]);
        $this->exams->paginatePublished(null);

        $this->exams->update($exam, ['name' => 'Tên mới']);

        $this->assertSame('Tên mới', $this->exams->paginatePublished(null)->first()->name);
    }

    public function test_unpublishing_or_deleting_an_exam_removes_it_from_the_list(): void
    {
        [$a, $b] = Exam::factory()->count(2)->create(['is_published' => true]);
        $this->assertCount(2, $this->exams->paginatePublished(null));

        $this->exams->update($a, ['is_published' => false]);
        $this->assertSame([$b->id], $this->exams->paginatePublished(null)->pluck('id')->all());

        $this->exams->delete($b);
        $this->assertCount(0, $this->exams->paginatePublished(null));
    }

    public function test_adding_a_question_refreshes_the_question_count(): void
    {
        $exam = Exam::factory()->withQuestions(1)->create(['is_published' => true]);
        $this->assertSame(1, $this->exams->paginatePublished(null)->first()->questions_count);

        $exam->questions()->create(['content' => 'Câu mới', 'position' => 2]);

        $this->assertSame(2, $this->exams->paginatePublished(null)->first()->questions_count);
    }

    public function test_pages_and_search_terms_are_cached_separately(): void
    {
        Exam::factory()->count(ExamService::PER_PAGE + 1)->create(['is_published' => true]);
        Exam::factory()->create(['name' => 'Laravel nâng cao', 'is_published' => true]);

        $this->assertCount(ExamService::PER_PAGE, $this->exams->paginatePublished(null, 1));
        $this->assertCount(2, $this->exams->paginatePublished(null, 2));

        $search = $this->exams->paginatePublished('laravel');
        $this->assertSame(['Laravel nâng cao'], $search->pluck('name')->all());
        $this->assertStringContainsString('q=laravel', $search->url(2));
    }
}
