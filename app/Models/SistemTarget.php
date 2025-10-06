<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemTarget extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['konfigurasi' => 'array'];


    public static function search($search)
    {
        $src = trim($search);
        return empty($src) ? static::query()
            : static::query()->where([['id', 'like', '%' . $src . '%']])
            ->orWhere('nama', 'like', '%' . $src . '%')
            ->orWhere('konfigurasi', 'like', '%' . $src . '%')
            ->orWhere('custom_option', 'like', '%' . $src . '%')
            ->orWhere('created_at', 'like', '%' . $src . '%')
            ->orWhere('updated_at', 'like', '%' . $src . '%');
    }
}
