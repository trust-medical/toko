<?php

declare(strict_types=1);

namespace TrustMedical\Toko;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use TrustMedical\Toko\Contracts\PostPublisherContract;
use TrustMedical\Toko\Events\PostSlugChanged;
use TrustMedical\Toko\Listeners\WriteSlugHistory;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Observers\PostObserver;
use TrustMedical\Toko\Services\PostPublisher;

final class TokoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 公開処理のサービスをDIで解決できるようにする
        $this->app->singleton(PostPublisherContract::class, PostPublisher::class);
        // パッケージ設定を読み込む
        $this->mergeConfigFrom(__DIR__.'/../config/toko.php', 'toko');
    }

    public function boot(): void
    {
        // パッケージのマイグレーションを読み込む
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // パッケージの翻訳を読み込む
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'toko');

        // slug変更の履歴を自動で保存
        Post::observe(PostObserver::class);

        // イベント: slug変更 -> 履歴保存
        Event::listen(PostSlugChanged::class, WriteSlugHistory::class);

        $this->configurePublishing();
    }

    protected function configurePublishing(): void
    {
        // 設定ファイルを公開できるようにする
        $this->publishes([
            __DIR__.'/../config/toko.php' => config_path('toko.php'),
        ], 'toko-config');

        // 翻訳ファイルを公開できるようにする
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/toko'),
        ], 'toko-translations');
    }
}
