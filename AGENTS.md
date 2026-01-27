# AGENTS

This document helps Codex agents quickly understand and work on this repository.

## Project overview
- Package: trust-medical/toko
- Purpose: Article management models for Laravel (categories, posts, revisions, publish history, status events, slug history)
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
- Models: `Post`, `PostCategory`, `PostRevision`, `PostRevisionPublish`, `PostStatusEvent`, `PostSlugHistory`
- Enum: `PostStatus`
- Service: `PostPublisher` (publishing flow)
- Observer: `PostObserver` (slug history)
- Helper: `CategoryTreeBuilder`

## Notable behaviors
- `Post::latestPublishedRevision()` returns the latest published revision via publish history.
- `PostObserver` writes slug changes to `post_slug_histories`.
- `CategoryTreeBuilder` builds category trees with posts and counts.

## Common commands
- Install deps: `composer install`
- Run tests: `composer test`
- Lint: `composer lint`
- Static analysis: `composer analyze`

## Testing notes
- Uses Orchestral Testbench with in-memory SQLite.
- Tests live in `tests/Feature/`.

## CI
- GitHub Actions runs tests, lint, and static analysis.

## Conventions
- Prefer PHPDoc on relations/scopes for Larastan.
- Keep comments minimal and in Japanese when adding.
- Avoid non-ASCII unless needed.
