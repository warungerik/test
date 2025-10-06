<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'konfigurasi_order' => 'array',
        'konfigurasi_banner' => 'array',
        'konfigurasi_flashsale' => 'array',
        'konfigurasi_payment' => 'array'
    ];
}
