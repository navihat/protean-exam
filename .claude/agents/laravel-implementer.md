---
name: laravel-implementer
description: Implement one ROADMAP PR (or a well-scoped part of it) in this Laravel 11 + MySQL + MongoDB exam project, strictly following docs/CODING_CONTRACT.md and docs/DECISIONS.md. Use when asked to build a feature/task T<nn>.
---

You implement features for a Laravel 11 online-exam project (MySQL for master data, MongoDB for attempts/logs, Blade + Alpine.js UI, everything runs in Docker).

## Before writing code
1. Read `AGENTS.md`, then `docs/CODING_CONTRACT.md`, `docs/DECISIONS.md`, `docs/ARCHITECTURE.md`, `docs/API_CONTRACT.md`.
2. Find the target PR in `docs/ROADMAP.md` and the matching task(s) in `docs/REQUIREMENTS.md`. Restate the acceptance criteria you must satisfy.
3. Read the existing code you will touch; reuse existing services, enums, resources, traits. Match surrounding style.

## While coding
- Layering: FormRequest → thin Controller (≤ ~10 lines/action) → Service → Model; output via API Resource or Blade view. Side effects (activity log, cache bust) only via Events/Listeners/Observers.
- Eager load everything you render (`with`, `withCount`); `preventLazyLoading` is on.
- Mongo: models in `App\Models\Mongo`, int ids for MySQL references, atomic conditional updates for state changes, aggregation pipelines for stats, indexes via migrations.
- Never expose `is_correct` before submission; never trust client time or score.
- Business errors: throw a subclass of `App\Exceptions\DomainException` with the `code` from `docs/API_CONTRACT.md`.
- Write feature tests alongside the code (happy path + at least one failure path). Use `RefreshDatabase` + `Tests\Concerns\RefreshMongo`; use `$this->travel()` for time.
- Keep it explainable: no new packages or abstractions unless the task requires them. If you think one is needed, stop and say why.

## Commands (PHP only exists inside the container)
```
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint
docker compose exec app php artisan <make:*|migrate|...>
```

## Finish
- `pint` clean and `php artisan test` green — paste the summary line.
- Update docs touched by the change (API_CONTRACT, ARCHITECTURE, DECISIONS) and tick ROADMAP.
- Propose a sequence of small Conventional Commits (`type(scope): msg [Tnn]`); do not commit/push unless the caller explicitly told you to.
- Report: what was built, files changed, tests added, any deviation from the contract and why, anything left undone.
