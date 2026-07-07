<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    protected $fillable = [
        'host', 'port', 'protocol', 'username', 'password',
        'is_active', 'last_checked_at', 'last_status', 'last_latency_ms', 'notes',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_checked_at' => 'datetime',
        'port'            => 'integer',
        'last_latency_ms' => 'integer',
    ];

    public function getDsnAttribute(): string
    {
        return "{$this->protocol}://{$this->host}:{$this->port}";
    }
}
