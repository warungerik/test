<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'product' => 'array',
        'payment' => 'array',
        'other_prices' => 'array',
        'seller_notes' => 'array'
    ];
}
