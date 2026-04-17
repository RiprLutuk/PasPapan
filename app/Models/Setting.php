<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const CACHE_KEY_ALL = 'settings.all';

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            self::flushCache($setting->key);
        });

        static::deleted(function (self $setting) {
            self::flushCache($setting->key);
        });
    }

    public static function getValue($key, $default = null)
    {
        $values = Cache::rememberForever(self::CACHE_KEY_ALL, static fn () => self::query()->pluck('value', 'key')->all());

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function flushCache(?string $key = null): void
    {
        Cache::forget(self::CACHE_KEY_ALL);

        if ($key !== null) {
            Cache::forget("setting.{$key}");
        }
    }
}
