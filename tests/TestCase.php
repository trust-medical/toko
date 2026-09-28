<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TrustMedical\Toko\Tests\Support\User;
use TrustMedical\Toko\TokoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [TokoServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // テストはメモリ内SQLiteで実行
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('toko.slug_history.enabled', true);
        $app['config']->set('toko.publishing.require_content_html', true);
        // パッケージ内のユーザ関連リレーション解決先をテスト用モデルに固定
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel標準 + パッケージのマイグレーションを実行
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->artisan('migrate')->run();
    }
}
