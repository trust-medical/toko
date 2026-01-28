<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_revisions', function (Blueprint $table) {
            $table->text('excerpt')->nullable()->after('title');
            $table->string('slug', 180)->nullable()->after('excerpt');
        });
    }

    public function down(): void
    {
        Schema::table('post_revisions', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'slug']);
        });
    }
};
