<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $guarded = ['id'];
    protected $table = 'feedbacks';

    protected $casts = [
        'information' => 'array'
    ];
}
