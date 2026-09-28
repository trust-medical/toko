<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // システム(バッチ等)による公開を許容するため nullable にする
        Schema::table('post_revision_publishes', function (Blueprint $table) {
            $table->unsignedBigInteger('published_by_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // NULLの行がある場合はNOT NULLに戻せないため変更しない
        if (DB::table('post_revision_publishes')->whereNull('published_by_user_id')->exists()) {
            return;
        }

        Schema::table('post_revision_publishes', function (Blueprint $table) {
            $table->unsignedBigInteger('published_by_user_id')->nullable(false)->change();
        });
    }
};
