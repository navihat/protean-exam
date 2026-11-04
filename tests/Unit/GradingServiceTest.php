<?php

namespace Tests\Unit;

use App\Services\Attempt\GradingService;
use PHPUnit\Framework\TestCase;

class GradingServiceTest extends TestCase
{
    /**
     * Question 1: option 11 correct. Question 2: options 21 and 22 correct. Question 3: option 31 correct.
     */
    private function snapshot(): array
    {
        $option = fn (int $id, bool $correct) => ['option_id' => $id, 'content' => "Option {$id}", 'is_correct' => $correct];

        return [
            ['question_id' => 1, 'content' => 'Q1', 'options' => [$option(11, true), $option(12, false)]],
            ['question_id' => 2, 'content' => 'Q2', 'options' => [$option(21, true), $option(22, true), $option(23, false)]],
            ['question_id' => 3, 'content' => 'Q3', 'options' => [$option(31, true), $option(32, false)]],
        ];
    }

    public function test_all_correct_answers_score_ten(): void
    {
        $grade = (new GradingService)->grade($this->snapshot(), [1 => [11], 2 => [22, 21], 3 => [31]]);

        $this->assertSame(3, $grade['correct_count']);
        $this->assertSame(3, $grade['total_questions']);
        $this->assertSame(10.0, $grade['score']);
    }

    public function test_multiple_choice_question_needs_exactly_the_correct_set(): void
    {
        $grading = new GradingService;

        $this->assertFalse($grading->grade($this->snapshot(), [2 => [21]])['results'][1]['is_correct']);
        $this->assertFalse($grading->grade($this->snapshot(), [2 => [21, 22, 23]])['results'][1]['is_correct']);
        $this->assertTrue($grading->grade($this->snapshot(), [2 => [21, 22]])['results'][1]['is_correct']);
    }

    public function test_option_ids_from_other_questions_are_ignored(): void
    {
        // 31 belongs to question 3, so for question 1 only 11 counts.
        $result = (new GradingService)->grade($this->snapshot(), [1 => [11, 31]])['results'][0];

        $this->assertTrue($result['is_correct']);
        $this->assertSame([11], $result['selected']);
    }

    public function test_unanswered_questions_are_wrong_and_score_is_rounded(): void
    {
        $grade = (new GradingService)->grade($this->snapshot(), [1 => [11]]);

        $this->assertSame(1, $grade['correct_count']);
        $this->assertSame(3.33, $grade['score']);
        $this->assertSame([], $grade['results'][2]['selected']);
        $this->assertSame([31], $grade['results'][2]['correct']);
    }

    public function test_empty_snapshot_scores_zero(): void
    {
        $this->assertSame(0.0, (new GradingService)->grade([], [])['score']);
    }
}
