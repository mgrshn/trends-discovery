<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'topics';

    protected $fillable = [
        'keyword', 'geo', 'source', 'seed_keyword', 'category_id',
        'status', 'score', 'volume', 'growth_pct', 'growth_3m',
        'growth_6m', 'growth_12m', 'sparkline', 'discovered_at',
        'last_scored_at', 'approved',
    ];

    protected $casts = [
        'sparkline'     => 'array',
        'approved'      => 'boolean',
        'score'         => 'float',
        'growth_pct'    => 'float',
        'growth_3m'     => 'float',
        'growth_6m'     => 'float',
        'growth_12m'    => 'float',
        'discovered_at' => 'datetime',
        'last_scored_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
