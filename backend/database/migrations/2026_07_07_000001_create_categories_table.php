<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE categories (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                google_category_ids JSONB NOT NULL DEFAULT \'[]\',
                created_at TIMESTAMPTZ,
                updated_at TIMESTAMPTZ
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
