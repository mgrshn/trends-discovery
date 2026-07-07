<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE topic_metrics_history (
                id BIGSERIAL PRIMARY KEY,
                topic_id BIGINT NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
                computed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                score NUMERIC(10, 4),
                growth_3m NUMERIC(10, 2),
                growth_6m NUMERIC(10, 2),
                growth_12m NUMERIC(10, 2),
                status VARCHAR(20)
                    CHECK (status IN (\'exploding\', \'regular\', \'peaked\', \'noise\'))
            )
        ');

        DB::statement('CREATE INDEX tmh_topic_id_idx ON topic_metrics_history (topic_id, computed_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_metrics_history');
    }
};
