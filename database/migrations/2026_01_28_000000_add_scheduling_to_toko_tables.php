<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('published_at');
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('post_revision_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('revision_id');
            $table->unsignedBigInteger('scheduled_by_user_id');

            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->foreign('post_id')
                ->references('id')->on('posts')
                ->cascadeOnDelete();

            $table->foreign('revision_id')
                ->references('id')->on('post_revisions')
                ->restrictOnDelete();

            $table->foreign('scheduled_by_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->unique(['post_id']);
            $table->index(['revision_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_revision_schedules');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropColumn('scheduled_at');
        });
    }
};
