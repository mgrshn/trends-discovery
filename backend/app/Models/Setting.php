<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = DB::table('settings')->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value, 'updated_at' => now()],
        );
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::get($key);
        return $val === null ? $default : filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $val = static::get($key);
        return $val === null ? $default : (int) $val;
    }
}
