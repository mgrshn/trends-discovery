<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = ['google_id', 'name', 'parent_id'];

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }
}
