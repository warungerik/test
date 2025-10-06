<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['price' => 'array', 'auto_update' => 'array', 'profit' => 'array'];
}
