<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamService
{
    public const PER_PAGE = 10;

    /**
     * Admin listing: every exam (draft or published), newest first, filtered by name.
     */
    public function paginateForAdmin(?string $search): LengthAwarePaginator
    {
        return Exam::query()
            ->search($search)
            ->withCount('questions')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array{name: string, description?: ?string, duration_minutes: int, is_published?: bool}  $data
     */
    public function create(array $data, User $author): Exam
    {
        $exam = new Exam($data);
        $exam->created_by = $author->id;
        $exam->save();

        return $exam;
    }

    /**
     * @param  array{name: string, description?: ?string, duration_minutes: int, is_published?: bool}  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam;
    }

    /**
     * Soft delete: attempts already taken keep pointing at a resolvable exam (D11).
     */
    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    public function loadWithQuestions(Exam $exam): Exam
    {
        return $exam->load('questions.options');
    }
}
