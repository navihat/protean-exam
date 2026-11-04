<?php

namespace App\Services\Attempt;

/**
 * Pure grading: no database, no clock, so it is easy to unit test (T12, D6).
 */
class GradingService
{
    /**
     * Grades answers against the snapshot only; the client never sends scores (C5.1).
     * A question is correct only when the selected set equals the correct set.
     * Option ids that do not belong to the question are ignored (C5.4).
     *
     * @param  list<array{question_id: int, content: string, options: list<array{option_id: int, content: string, is_correct: bool}>}>  $snapshot
     * @param  array<int, list<int>>  $answers  question_id => selected option ids
     * @return array{
     *     results: list<array{question_id: int, content: string, selected: list<int>, correct: list<int>, is_correct: bool}>,
     *     correct_count: int,
     *     total_questions: int,
     *     score: float
     * }
     */
    public function grade(array $snapshot, array $answers): array
    {
        $results = array_map(function (array $question) use ($answers) {
            $optionIds = array_column($question['options'], 'option_id');
            $correct = array_column(array_filter($question['options'], fn (array $option) => $option['is_correct']), 'option_id');
            $selected = array_values(array_intersect($optionIds, $answers[$question['question_id']] ?? []));

            sort($correct);
            sort($selected);

            return [
                'question_id' => $question['question_id'],
                'content' => $question['content'],
                'selected' => $selected,
                'correct' => $correct,
                'is_correct' => $selected === $correct,
            ];
        }, $snapshot);

        $total = count($results);
        $correctCount = count(array_filter($results, fn (array $result) => $result['is_correct']));

        return [
            'results' => $results,
            'correct_count' => $correctCount,
            'total_questions' => $total,
            'score' => $total > 0 ? round($correctCount / $total * 10, 2) : 0.0,
        ];
    }
}
