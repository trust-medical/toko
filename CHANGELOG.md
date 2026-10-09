# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Laravel 13 support. `laravel/framework` is now `^12.0|^13.0`, and CI tests Laravel 12 and 13 (Testbench 10 and 11).

## [0.8.0] - 2026-09-28

### Added
- Migration making `post_revision_publishes.published_by_user_id` nullable, so system publishes are allowed.
- `PostEditor::update()` publishes or schedules the latest revision when no new revision is given.
- Status lifecycle, events, configuration and upgrade guide sections in the README.

### Changed
- `PostScheduler::schedule()` rejects posts that are already published.
- `PostPublisher::publish()` rejects a revision that has already been published, with a clear exception instead of a unique-constraint error.
- Re-publishing a published post and rescheduling a scheduled post no longer record a status event or dispatch `PostStatusChanged`.
- `PostEditor::create()` always creates the post as `Draft` first and validates `author_user_id` / `category_id`.
- `PostEditor::update()` reschedules through `PostScheduler`, ignores `scheduled_at` unless the post is scheduled, and ignores `published_at` while it is scheduled.
- `$publishedAt` / `$scheduledAt` on the publisher/scheduler contracts accept any `DateTimeInterface`.
- `toko:publish-scheduled` processes the oldest due posts first, keeps going after a failure (exit code 1), and publishes with a `null` publisher when the scheduling user is missing.
- Slug history is written after a successful update (`updated` event). An old slug belongs to the post that used it most recently, and slugs that become live again are removed from the history.
- The package requires `laravel/framework` instead of individual `illuminate/*` packages.
- Removed the `version` field from `composer.json`. Versions are defined by git tags.
- CI uses `actions/checkout@v7` (Node.js 24).

### Removed
- Laravel 11 support. It is past end of life and every 11.x release has unpatched security advisories. Laravel 12 is required.

### Fixed
- `PostRevisionRestorer` no longer writes a `null` slug/title to the post, and it copies DB default values of the source revision correctly.
- `PostFactory::scheduled()` uses `scheduled_at` instead of a future `published_at`.
- PHPDoc types of `PostRevisionPublish`, `PostRevisionSchedule`, `Post` and `PostStatusEvent` now match the schema and casts.

## [0.7.0] - 2026-01-28

### Added
- `PostRevisionRestorer` service for restoring a revision as a new revision.
- `excerpt` and `slug` on `PostRevision`; publishing copies them to the post (0.6.0).
- `PostEditor` service for creating and updating posts with revisions (0.5.0).
- Scheduled publishing: `PostScheduler`, `post_revision_schedules`, `posts.scheduled_at` and the `toko:publish-scheduled` command (0.4.0).

## [0.3.0] - 2026-01-28

### Added
- Translatable `PostStatus` labels (`toko::post-status.*`) with English and Japanese files.

## [0.2.0] - 2026-01-28

### Added
- `HasColor` / `HasLabel` on `PostStatus` (Filament compatible).

## [0.1.0] - 2026-01-27

### Added
- Initial release: models, migrations, `PostPublisher`, events, config, slug history, scopes, category tree helpers, factories and `TokoSeeder`.
