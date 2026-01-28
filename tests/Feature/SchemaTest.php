<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use TrustMedical\Toko\Tests\TestCase;

final class SchemaTest extends TestCase
{
    public function test_it_creates_all_tables(): void
    {
        $this->assertTrue(Schema::hasTable('post_categories'));
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('post_revisions'));
        $this->assertTrue(Schema::hasTable('post_revision_publishes'));
        $this->assertTrue(Schema::hasTable('post_revision_schedules'));
        $this->assertTrue(Schema::hasTable('post_status_events'));
        $this->assertTrue(Schema::hasTable('post_slug_histories'));
    }
}
