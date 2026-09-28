# AGENTS

This document helps Codex agents quickly understand and work on this repository.

## Project overview
- Package: trust-medical/toko
- Purpose: Article management models for Laravel (categories, posts, revisions, schedules, publish history, status events, slug history)
- PHP: 8.3+
- Laravel: 11 / 12

## Key directories
- `src/` core package code (models, services, observers, support)
- `database/migrations/` package migrations
- `database/factories/` Eloquent factories
- `database/seeders/` sample seeder
- `tests/` Testbench-based tests
- `resources/boost/guidelines/` Laravel Boost guidelines

## Important classes
- Models: `Post`, `PostCategory`, `PostRevision`, `PostRevisionPublish`, `PostRevisionSchedule`, `PostStatusEvent`, `PostSlugHistory`
- Enum: `PostStatus`
- Services (bound by contracts in `src/Contracts`): `PostPublisher` (publishing flow), `PostScheduler` (scheduling flow), `PostEditor` (create/update post + revision), `PostRevisionRestorer` (restore revision)
- Command: `toko:publish-scheduled` (`PublishScheduledPosts`)
- Events: `PostPublished`, `PostStatusChanged`, `PostSlugChanged`
- Observer / Listener: `PostObserver` dispatches `PostSlugChanged` on `updated`; `WriteSlugHistory` writes `post_slug_histories`
- Helper: `CategoryTreeBuilder`
- Config: `config/toko.php` (`default_status`, `slug_history.enabled`, `publishing.require_content_html`)

## Notable behaviors
- `PostRevision` stores title + content + excerpt + slug; publishing copies title/excerpt/slug to `Post` (null keeps the current value).
- While a post is published, `Post` title/excerpt/slug change only by publishing a revision.
- `PostPublisher` publishes a revision, sets `Post` as published and clears any schedule. The same revision cannot be published twice (restore it as a new revision instead). `publishedBy` may be null.
- `PostScheduler` stores the reservation in `post_revision_schedules` (one per post) and `posts.scheduled_at`. Published posts cannot be scheduled.
- Status events and `PostStatusChanged` are recorded only when the status actually changes (no event for re-publish / reschedule).
- `PostEditor::create()` always creates a Draft first, then transitions. `update()` uses the latest revision when publishing/scheduling without a new one, and reschedules via `PostScheduler`.
- `toko:publish-scheduled` publishes due schedules oldest first, isolates failures per post and returns FAILURE if any failed.
- Slug history: an old slug belongs to its latest owner; slugs that become live again are removed from history.
- `Post::latestPublishedRevision()` returns the latest published revision via publish history.
- `CategoryTreeBuilder` builds category trees with posts and direct-post counts.
- `PostStatus::getLabel()` uses translations under the `toko::post-status.*` namespace with fallback labels.
- Services throw `InvalidArgumentException` on invalid input before writing, and run inside DB transactions.

## Common commands
- Install deps: `composer install`
- Run tests: `composer test`
- Lint: `composer lint`
- Static analysis: `composer analyze`
- In Docker: `docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 composer test`

## Testing notes
- Uses Orchestral Testbench with in-memory SQLite.
- Tests live in `tests/Feature/` (`ModelBehaviorTest`, `ServiceRobustnessTest`, `SchemaTest`).

## CI
- GitHub Actions runs tests, lint, and static analysis.

## Conventions
- Keep `README.md` and `README.ja.md` in sync, and record changes in `CHANGELOG.md` (bump `version` in `composer.json`).
- Prefer PHPDoc on relations/scopes for Larastan.
- Keep comments minimal and in Japanese when adding.
- Avoid non-ASCII unless needed.
