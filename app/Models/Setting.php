<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'type'])]
class Setting extends Model
{
    public static function get (string $key, mixed $default = null): mixed {
        return Cache::rememberForever(
            "setting:{$key}",
            function () use ($key, $default) {
                $setting = static::where("key", $key)->first();

                if ($setting === null) {
                    return $default;
                }

                return match ($setting->type) {
                    'boolean' => filter_var(
                        $setting->value,
                        FILTER_VALIDATE_BOOLEAN
                    ),

                    'integer' => (int) $setting->value,

                    'float' => (float) $setting->value,

                    'json' => json_decode($setting->value, true),

                    default => $setting->value,
                };
            }
        );
    }

    public static function set(
        string $key,
        mixed $value,
        string $type = 'string'
    ): void {
        $storedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_decode($value),
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key'=> $key],
            [
                'value'=> $storedValue,
                'type'=> $type,
            ]
        );

        Cache::forget("setting:{$key}");
    }
}
