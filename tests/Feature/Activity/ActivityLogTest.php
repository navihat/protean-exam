<?php

namespace Tests\Feature\Activity;

use App\Models\Exam;
use App\Models\Mongo\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RefreshMongo;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase, RefreshMongo;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_creating_an_exam_records_one_log_with_the_acting_admin(): void
    {
        $this->actingAs($this->admin)->post(route('admin.exams.store'), [
            'name' => 'Đề mới',
            'duration_minutes' => 20,
        ]);

        $exam = Exam::firstWhere('name', 'Đề mới');
        $log = ActivityLog::where('action', 'exam.created')->sole();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('exam', $log->subject_type);
        $this->assertSame($exam->id, $log->subject_id);
        $this->assertSame('Đề mới', $log->meta['name']);
        $this->assertNotNull($log->ip);
    }

    public function test_updating_an_exam_records_the_changed_fields(): void
    {
        $exam = Exam::factory()->create(['name' => 'Cũ', 'duration_minutes' => 10, 'is_published' => false]);

        $this->actingAs($this->admin)->put(route('admin.exams.update', $exam), [
            'name' => 'Mới',
            'duration_minutes' => 10,
        ]);

        $log = ActivityLog::where('action', 'exam.updated')->sole();
        $this->assertSame($exam->id, $log->subject_id);
        $this->assertSame(['name'], $log->meta['fields']);
    }

    public function test_saving_an_exam_without_changes_records_nothing(): void
    {
        $exam = Exam::factory()->create(['name' => 'Giữ nguyên', 'duration_minutes' => 10, 'is_published' => false]);

        $this->actingAs($this->admin)->put(route('admin.exams.update', $exam), [
            'name' => 'Giữ nguyên',
            'duration_minutes' => 10,
        ]);

        $this->assertSame(0, ActivityLog::where('action', 'exam.updated')->count());
    }

    public function test_soft_deleting_an_exam_records_a_log(): void
    {
        $exam = Exam::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.exams.destroy', $exam));

        $this->assertSame($exam->id, ActivityLog::where('action', 'exam.deleted')->sole()->subject_id);
    }
}
