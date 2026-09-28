# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

`trust-medical/toko` is a Laravel 11/12 package (PHP 8.3+) that provides article management: categories, posts, immutable revisions, publish history, scheduled publishing, status audit logs and slug history. `AGENTS.md` holds a parallel summary for other agents. Keep both files consistent when behavior changes.

## Commands

Development prefers Docker. The `composer:2` image includes pdo_sqlite, which is enough to run the tests.

```bash
# Run everything in the container
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 composer lint      # pint (rewrites files)
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 composer analyze   # larastan level 6 (src + tests)

# Single test / single file
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 vendor/bin/phpunit --filter test_editor_reschedule_goes_through_scheduler
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 vendor/bin/phpunit tests/Feature/ServiceRobustnessTest.php
```

The container lacks `ext-intl`, which the dev dependency `filament/support` requires. When running `composer update` or `require` in it, add `--ignore-platform-req=ext-intl`. `composer.lock` is gitignored.

CI (`.github/workflows/tests.yml`) runs phpunit, pint and phpstan across PHP 8.3/8.4 × Laravel 11/12.

## Architecture

### Post vs. revision

- `Post` holds the *current/public* values: title, excerpt, slug, status, `published_at`, `scheduled_at`.
- `PostRevision` is an immutable snapshot (`UPDATED_AT = null`) with title/excerpt/slug, `content_json` (TipTap) and `content_html`.
- Publishing copies a revision's title/excerpt/slug onto the Post. A `null` on the revision keeps the post's value.
- While a post is Published, its title/excerpt/slug must only change by publishing a revision. `PostEditor::update()` strips those keys for published posts, and `PostRevisionRestorer` doesn't touch a published post.

### Services own all state transitions

The services live in `src/Services`, are bound as singletons against `src/Contracts`, and are registered in `TokoServiceProvider`.

- `PostPublisher` and `PostScheduler` are the primitives that write `post_revision_publishes`, `post_revision_schedules`, `post_status_events` and dispatch events.
- `PostEditor` is the high-level create/update API. It composes the publisher and scheduler **via their contracts**, so it never writes schedules or publishes itself. `create()` always inserts a Draft and then transitions.
- `PostRevisionRestorer` copies a revision into a new one.
- The `toko:publish-scheduled` command (`src/Console/Commands`) locks each due schedule and calls the publisher, one transaction per post. Failures are isolated per post, and the command returns FAILURE if any occurred.

Invariants the services enforce. Preserve them when changing code:

- A revision can be published at most once (DB unique `post_id, revision_id` + pre-check). Re-publishing old content goes through Restorer → new revision.
- Published posts cannot be scheduled. There is at most one schedule per post (unique `post_id`).
- A `PostStatusEvent` and `PostStatusChanged` are emitted only when the status actually changes. `PostPublished` fires on every publish.
- Leaving Scheduled deletes the schedule row and clears `scheduled_at`. A Scheduled post has `published_at = null`.
- Invalid input throws `InvalidArgumentException` *before* any write. Everything runs in `DB::transaction` (nested calls use savepoints), and events are dispatched inside the transaction.

### Slug history

`PostObserver::updated` (after a successful save, via `wasChanged`/`getOriginal`) dispatches `PostSlugChanged`, and the `WriteSlugHistory` listener writes `post_slug_histories`:

- `old_slug` is globally unique and reassigned to its latest owner (`updateOrCreate`).
- A slug that becomes live again is deleted from history.
- It is gated by `config('toko.slug_history.enabled')`.

### Other cross-cutting pieces

- **User model**: user relations resolve through `Models\Concerns\ResolvesUserModel` (`auth.providers.users.model`, falling back to `App\Models\User`). Migrations hard-code FKs to `users.id`.
- **Filament**: `Contracts\HasLabel` / `HasColor` conditionally extend Filament's interfaces when Filament is installed. `PostStatus::getLabel()` reads `toko::post-status.*` translations (`resources/lang/{en,ja}`) with hard-coded fallbacks.
- **Category trees**: `CategoryTreeBuilder` builds category trees in memory from one query (eager-loaded posts + `withCount`). Counts are direct posts only.
- **Config**: `config/toko.php` has `default_status`, `slug_history.enabled` and `publishing.require_content_html`.
- **Factories**: factories under `database/factories` write models directly and do not create publish history.

### Schema changes

- Add a new migration. Don't edit shipped ones, except for comments.
- Column changes use native `->change()` (Laravel 11+).
- `down()` must tolerate existing data. Testbench rolls migrations back on teardown, so a `down()` that fails on test data breaks the suite.

## Testing notes

- Tests use Orchestral Testbench with in-memory SQLite. `tests/TestCase.php` loads the Laravel and package migrations and points the user model at `tests/Support/User.php`.
- SQLite FK constraints are **not** enforced in tests, so deleting a referenced user works there but would fail on MySQL.
- `tests/Feature/ServiceRobustnessTest.php` covers the service invariants above and the README quick-start flow. Update it when README examples change.

## Conventions

- Code comments are minimal and written in Japanese. Otherwise avoid non-ASCII.
- Add PHPDoc on relations, scopes and `@property` blocks for Larastan. Factories carry `@extends Factory<Model>`.
- Pint (current version) converts FQCNs in PHPDoc into `use` imports. Run lint before committing.
- Keep `README.md` and `README.ja.md` structurally in sync.
- Record changes in `CHANGELOG.md` (Keep a Changelog) and bump `"version"` in `composer.json`. The 0.x series bumps the minor version for behavior changes, and user-facing behavior changes go in the README "Upgrading" section.
