# Contributing

コントリビューション歓迎です。以下の手順と方針に沿ってください。

## 開発環境

- PHP 8.3+
- Composer

## テスト / Lint / 静的解析

```bash
composer test
composer lint
composer analyze
```

## 方針

- 破壊的変更は避け、必要な場合は README / CHANGELOG に明記する
- 追加する機能にはテストを添付する
- コードフォーマットは Pint に従う
- Eloquent の relation / scope には PHPDoc を記載する
