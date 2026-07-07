<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE proxies (
                id              BIGSERIAL PRIMARY KEY,
                host            VARCHAR(255) NOT NULL,
                port            INTEGER      NOT NULL,
                protocol        VARCHAR(10)  NOT NULL DEFAULT \'http\'
                                    CHECK (protocol IN (\'http\', \'https\', \'socks5\')),
                username        VARCHAR(255),
                password        VARCHAR(255),
                is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
                last_checked_at TIMESTAMPTZ,
                last_status     VARCHAR(20)
                                    CHECK (last_status IN (\'ok\', \'error\', \'timeout\')),
                last_latency_ms INTEGER,
                notes           TEXT,
                created_at      TIMESTAMPTZ DEFAULT NOW(),
                updated_at      TIMESTAMPTZ DEFAULT NOW()
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
