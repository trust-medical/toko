<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // post_categories（階層カテゴリ：隣接リスト parent_id）
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')->on('post_categories')
                ->nullOnDelete();

            $table->index(['parent_id', 'sort_order']);
        });

        // posts（記事メタ）
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('author_user_id');   // users.id
            $table->unsignedBigInteger('category_id');      // post_categories.id

            $table->string('title', 255);
            $table->text('excerpt')->nullable();

            $table->string('slug', 180)->unique();

            // status: 0=draft, 1=scheduled, 2=published, 3=archived
            $table->unsignedTinyInteger('status')->default(0);

            // scheduled/published の公開開始日時（draftでも予約日時を持てる）
            $table->dateTime('published_at')->nullable();

            $table->timestamps();

            $table->foreign('author_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->foreign('category_id')
                ->references('id')->on('post_categories')
                ->restrictOnDelete();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['author_user_id', 'created_at']);
        });

        // post_revisions（改稿履歴：TipTap JSON + 公開時HTMLキャッシュ）
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('post_id');           // posts.id
            $table->unsignedBigInteger('editor_user_id');    // users.id

            $table->string('title', 255);

            // TipTap本体
            $table->json('content_json');

            // 公開時に生成して保存（下書きはNULLでも可だが、公開時は必ず埋める運用）
            $table->mediumText('content_html')->nullable();

            $table->string('editor', 32)->default('tiptap');
            $table->string('editor_version', 32)->nullable();
            $table->unsignedInteger('schema_version')->default(1);

            $table->string('change_note', 255)->nullable();

            // revisionは基本不変なので updated_at は省略（必要なら追加可）
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('post_id')
                ->references('id')->on('posts')
                ->cascadeOnDelete();

            $table->foreign('editor_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->index(['post_id', 'created_at']);
            $table->index(['editor_user_id', 'created_at']);
        });

        // post_revision_publishes（公開履歴）
        Schema::create('post_revision_publishes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('post_id');               // posts.id
            $table->unsignedBigInteger('revision_id');           // post_revisions.id
            $table->unsignedBigInteger('published_by_user_id');  // users.id
            $table->dateTime('published_at')->useCurrent();

            $table->foreign('post_id')
                ->references('id')->on('posts')
                ->cascadeOnDelete();

            $table->foreign('revision_id')
                ->references('id')->on('post_revisions')
                ->restrictOnDelete();

            $table->foreign('published_by_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->index(['post_id', 'published_at']);
            $table->unique(['post_id', 'revision_id'], 'uk_post_pub_post_revision');
        });

        // post_status_events（ステータス変更履歴）
        Schema::create('post_status_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('post_id');

            // status: 0=draft, 1=scheduled, 2=published, 3=archived
            $table->unsignedTinyInteger('from_status')->nullable();
            $table->unsignedTinyInteger('to_status');

            // 手動/自動（バッチ等）を想定して nullable 推奨
            $table->unsignedBigInteger('changed_by_user_id')->nullable();

            $table->string('note', 255)->nullable();
            $table->dateTime('changed_at')->useCurrent();

            $table->foreign('post_id')
                ->references('id')->on('posts')
                ->cascadeOnDelete();

            $table->foreign('changed_by_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['post_id', 'changed_at']);
            $table->index(['to_status', 'changed_at']);
        });

        // post_slug_histories（slug変更時のリダイレクト等に利用）
        Schema::create('post_slug_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('post_id');
            $table->string('old_slug', 180)->unique();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('post_id')
                ->references('id')->on('posts')
                ->cascadeOnDelete();

            $table->index(['post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_slug_histories');
        Schema::dropIfExists('post_status_events');
        Schema::dropIfExists('post_revision_publishes');

        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
    }
};
