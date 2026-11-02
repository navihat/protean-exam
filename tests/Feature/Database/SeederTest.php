<?php

namespace Tests\Feature\Database;

use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_student_and_sample_exams(): void
    {
        $this->seed();

        $this->assertSame(UserRole::Admin, User::where('email', 'admin@example.com')->first()->role);
        $this->assertSame(UserRole::Student, User::where('email', 'student@example.com')->first()->role);
        $this->assertGreaterThanOrEqual(3, Exam::published()->count());

        Exam::with('questions.options')->get()->each(function (Exam $exam) {
            $this->assertNotEmpty($exam->questions, "{$exam->name} has no questions");
            $exam->questions->each(fn (Question $q) => $this->assertTrue(
                $q->options->contains('is_correct', true),
                "Question {$q->id} has no correct option",
            ));
        });
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed();
        $counts = [User::count(), Exam::count(), Question::count(), Option::count()];

        $this->seed();

        $this->assertSame($counts, [User::count(), Exam::count(), Question::count(), Option::count()]);
    }
}
