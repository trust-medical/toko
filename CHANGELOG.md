# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- PostPublisher service for unified publishing flow.
- PostPublisherContract for DI-friendly customization.
- Events for publish/status/slug changes.
- Config file (toko.php) for toggles.
- latestPublishedRevision helper on Post.
- Slug history observer for Post slug changes.
- Additional Post scopes (byAuthor, inCategory, publishedAt, scheduledBetween, visible).
- Factories and TokoSeeder for sample data.

## [0.1.0] - 2026-01-27

### Added
- Initial release of Toko models, migrations, and helpers.
