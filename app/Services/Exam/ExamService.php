<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class ExamService
{
    public const PER_PAGE = 10;

    private const LIST_VERSION_KEY = 'exams:list-version';

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
     * Student listing: published exams only, cached per page and search term (T19, D14).
     *
     * The key embeds a version number. Observers bump it on every exam or question
     * change, so all cached pages become unreachable at once without needing cache
     * tags (unsupported by the `database` store). Old entries expire by TTL.
     */
    public function paginatePublished(?string $search, int $page = 1): LengthAwarePaginator
    {
        // Name search is case-insensitive in MySQL, so "Laravel" and "laravel" share one entry.
        $search = mb_strtolower(trim((string) $search));
        $key = sprintf('exams:v%d:page:%d:q:%s', $this->listVersion(), $page, md5($search));

        $exams = Cache::remember($key, config('exam.list_cache_seconds'), fn () => Exam::query()
            ->published()
            ->search($search)
            ->withCount('questions')
            ->latest('id')
            ->paginate(self::PER_PAGE, page: $page)
            // Not withQueryString(): the cached query string must depend only on the cache key.
            ->appends(array_filter(['q' => $search])));

        // The cached paginator remembers the URL path of the request that filled the cache
        // (web page or API). Reset it so each caller gets links to its own URL.
        return $exams->withPath(Paginator::resolveCurrentPath());
    }

    public function flushPublishedListCache(): void
    {
        // add() is a no-op when the key exists; increment() needs an existing key on the database store.
        Cache::add(self::LIST_VERSION_KEY, 1);
        Cache::increment(self::LIST_VERSION_KEY);
    }

    private function listVersion(): int
    {
        return (int) Cache::rememberForever(self::LIST_VERSION_KEY, fn () => 1);
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
