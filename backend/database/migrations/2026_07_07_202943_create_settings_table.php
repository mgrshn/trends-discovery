<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE settings (
                key        VARCHAR(100) PRIMARY KEY,
                value      TEXT,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )
        ');

        DB::table('settings')->insert([
            ['key' => 'auto_parse_enabled',       'value' => 'true'],
            ['key' => 'parse_interval_minutes',    'value' => '30'],
            ['key' => 'ingest_last_dispatched_at', 'value' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
