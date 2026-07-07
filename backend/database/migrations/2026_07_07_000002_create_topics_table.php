<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE topics (
                id BIGSERIAL PRIMARY KEY,
                keyword VARCHAR(500) NOT NULL,
                geo VARCHAR(10) NOT NULL DEFAULT \'\',
                source VARCHAR(50) NOT NULL DEFAULT \'trending\'
                    CHECK (source IN (\'trending\', \'related_rising\', \'breakdown\', \'manual\')),
                seed_keyword VARCHAR(500),
                category_id BIGINT REFERENCES categories(id) ON DELETE SET NULL,
                status VARCHAR(20)
                    CHECK (status IN (\'exploding\', \'regular\', \'peaked\', \'noise\')),
                score NUMERIC(10, 4),
                volume INTEGER,
                growth_3m NUMERIC(10, 2),
                growth_6m NUMERIC(10, 2),
                growth_12m NUMERIC(10, 2),
                sparkline JSONB,
                discovered_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                last_scored_at TIMESTAMPTZ,
                approved BOOLEAN,
                created_at TIMESTAMPTZ,
                updated_at TIMESTAMPTZ,
                UNIQUE (keyword, geo)
            )
        ');

        DB::statement('CREATE INDEX topics_status_idx ON topics (status)');
        DB::statement('CREATE INDEX topics_score_idx ON topics (score DESC NULLS LAST)');
        DB::statement('CREATE INDEX topics_category_idx ON topics (category_id)');
        DB::statement('CREATE INDEX topics_approved_idx ON topics (approved)');
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
