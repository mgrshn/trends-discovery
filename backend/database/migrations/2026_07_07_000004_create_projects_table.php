<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE projects (
                id BIGSERIAL PRIMARY KEY,
                user_id BIGINT,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMPTZ,
                updated_at TIMESTAMPTZ
            )
        ');

        DB::statement('
            CREATE TABLE project_topics (
                project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
                topic_id BIGINT NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
                added_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                PRIMARY KEY (project_id, topic_id)
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('project_topics');
        Schema::dropIfExists('projects');
    }
};
