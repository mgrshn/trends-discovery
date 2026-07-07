<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE topics ADD COLUMN IF NOT EXISTS growth_pct NUMERIC(12, 2)');
        DB::statement('CREATE INDEX IF NOT EXISTS topics_growth_pct_idx ON topics (growth_pct DESC NULLS LAST)');
        DB::statement('CREATE INDEX IF NOT EXISTS topics_geo_idx ON topics (geo)');
        DB::statement('CREATE INDEX IF NOT EXISTS topics_source_idx ON topics (source)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS topics_growth_pct_idx');
        DB::statement('DROP INDEX IF EXISTS topics_geo_idx');
        DB::statement('DROP INDEX IF EXISTS topics_source_idx');
        DB::statement('ALTER TABLE topics DROP COLUMN IF EXISTS growth_pct');
    }
};
