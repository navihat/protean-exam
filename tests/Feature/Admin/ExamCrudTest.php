<?php

namespace Tests\Feature\Admin;

use App\Models\Exam;
use App\Models\User;
use App\Services\Exam\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_index_lists_exams_with_pagination(): void
    {
        Exam::factory()->count(ExamService::PER_PAGE + 2)->withQuestions(1)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.exams.index'));

        $response->assertOk();
        $this->assertCount(ExamService::PER_PAGE, $response->viewData('exams'));
        $this->assertSame(ExamService::PER_PAGE + 2, $response->viewData('exams')->total());

        $this->actingAs($this->admin)->get(route('admin.exams.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('exams', fn ($exams) => $exams->count() === 2);
    }

    public function test_index_searches_by_name_and_keeps_query_in_pagination_links(): void
    {
        Exam::factory()->create(['name' => 'Laravel căn bản']);
        Exam::factory()->create(['name' => 'PHP cơ bản']);

        $response = $this->actingAs($this->admin)->get(route('admin.exams.index', ['q' => 'laravel']));

        $response->assertOk()->assertSee('Laravel căn bản')->assertDontSee('PHP cơ bản');
        $this->assertStringContainsString('q=laravel', $response->viewData('exams')->url(2));
    }

    public function test_search_rejects_non_string_query(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.exams.index', ['q' => ['x']]))
            ->assertSessionHasErrors('q');
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        Exam::factory()->create(['name' => 'Giảm 50% học phí']);
        Exam::factory()->create(['name' => 'Đề 500 câu']);

        $this->actingAs($this->admin)->get(route('admin.exams.index', ['q' => '50%']))
            ->assertOk()
            ->assertSee('Giảm 50% học phí')
            ->assertDontSee('Đề 500 câu');
    }

    public function test_create_and_edit_forms_render(): void
    {
        $exam = Exam::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.exams.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.exams.edit', $exam))
            ->assertOk()
            ->assertSee($exam->name);
    }

    public function test_admin_can_create_an_exam(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.exams.store'), [
            'name' => 'Đề mới',
            'description' => 'Mô tả',
            'duration_minutes' => 20,
            'is_published' => '1',
        ]);

        $exam = Exam::firstWhere('name', 'Đề mới');
        $response->assertRedirect(route('admin.exams.show', $exam));
        $this->assertSame(20, $exam->duration_minutes);
        $this->assertTrue($exam->is_published);
        $this->assertSame($this->admin->id, $exam->created_by);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.exams.create'))
            ->post(route('admin.exams.store'), ['name' => '', 'duration_minutes' => 0])
            ->assertRedirect(route('admin.exams.create'))
            ->assertSessionHasErrors(['name', 'duration_minutes']);

        $this->assertSame(0, Exam::count());
    }

    public function test_admin_can_update_an_exam_and_unpublish_it(): void
    {
        $exam = Exam::factory()->create(['name' => 'Cũ', 'is_published' => true]);

        $this->actingAs($this->admin)->put(route('admin.exams.update', $exam), [
            'name' => 'Mới',
            'duration_minutes' => 30,
            // is_published omitted = unchecked checkbox
        ])->assertRedirect(route('admin.exams.show', $exam));

        $exam->refresh();
        $this->assertSame('Mới', $exam->name);
        $this->assertSame(30, $exam->duration_minutes);
        $this->assertFalse($exam->is_published);
    }

    public function test_admin_can_soft_delete_an_exam(): void
    {
        $exam = Exam::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.exams.destroy', $exam))
            ->assertRedirect(route('admin.exams.index'));

        $this->assertSoftDeleted($exam);
    }

    public function test_show_page_renders_questions_without_lazy_loading(): void
    {
        $exam = Exam::factory()->withQuestions(3)->create();

        // preventLazyLoading is on, so an N+1 in the view would throw here.
        $this->actingAs($this->admin)->get(route('admin.exams.show', $exam))
            ->assertOk()
            ->assertSee($exam->questions()->first()->content);
    }

    public function test_student_cannot_manage_exams(): void
    {
        $student = User::factory()->create();
        $exam = Exam::factory()->create();

        $this->actingAs($student)->get(route('admin.exams.index'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.exams.store'), ['name' => 'x', 'duration_minutes' => 5])->assertForbidden();
        $this->actingAs($student)->delete(route('admin.exams.destroy', $exam))->assertForbidden();

        $this->assertNotSoftDeleted($exam);
    }
}
