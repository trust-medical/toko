# Contributing

Contributions are welcome. Please follow the steps and guidelines below.

## Development environment

- PHP 8.3+
- Composer

## Test / Lint / Static analysis

```bash
composer test
composer lint
composer analyze
```

## Guidelines

- Avoid breaking changes; if unavoidable, document them in README / CHANGELOG
- Add tests for new functionality
- Follow Pint for code style
- Add PHPDoc for Eloquent relations / scopes
