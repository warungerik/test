<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['price' => 'array', 'auto_update' => 'array'];

    public function categories()
    {
        return $this->belongsTo(CategoryPPOB::class, 'category_id', 'id')->withDefault();
    }
    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault();
    }
}
