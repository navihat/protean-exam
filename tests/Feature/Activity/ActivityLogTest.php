<?php

namespace Tests\Feature\Activity;

use App\Models\Exam;
use App\Models\Mongo\ActivityLog;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
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

    public function test_question_create_update_and_delete_are_recorded(): void
    {
        $exam = Exam::factory()->create();
        $options = [
            ['content' => 'A', 'is_correct' => '1'],
            ['content' => 'B', 'is_correct' => '0'],
        ];

        $this->actingAs($this->admin)->post(route('admin.exams.questions.store', $exam), [
            'content' => 'Câu 1',
            'options' => $options,
        ]);
        $question = $exam->questions()->sole();

        // Only the options change: the question row itself must still count as updated.
        $options[1]['content'] = 'B sửa';
        $this->actingAs($this->admin)->put(route('admin.exams.questions.update', [$exam, $question]), [
            'content' => 'Câu 1',
            'options' => $options,
        ]);

        $this->actingAs($this->admin)->delete(route('admin.exams.questions.destroy', [$exam, $question]));

        foreach (['question.created', 'question.updated', 'question.deleted'] as $action) {
            $log = ActivityLog::where('action', $action)->sole();
            $this->assertSame($question->id, $log->subject_id, $action);
            $this->assertSame($exam->id, $log->meta['exam_id'], $action);
            $this->assertSame($this->admin->id, $log->user_id, $action);
        }
    }

    public function test_nothing_is_recorded_when_the_transaction_rolls_back(): void
    {
        $exam = Exam::factory()->create();
        ActivityLog::query()->delete();

        try {
            DB::transaction(function () use ($exam) {
                $exam->questions()->create(['content' => 'Sẽ bị rollback', 'position' => 1]);

                throw new RuntimeException('options failed');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(0, Question::count());
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_web_register_login_and_logout_are_recorded(): void
    {
        $this->post(route('register'), [
            'name' => 'Học sinh',
            'email' => 'hs@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $user = User::firstWhere('email', 'hs@example.com');

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'hs@example.com', 'password' => 'password']);

        $this->assertSame($user->id, ActivityLog::where('action', 'auth.registered')->sole()->user_id);
        // Registration logs the user in, then the explicit login: two entries.
        $this->assertSame(2, ActivityLog::where('action', 'auth.login')->where('user_id', $user->id)->count());
        $this->assertSame($user->id, ActivityLog::where('action', 'auth.logout')->sole()->user_id);
    }
}
