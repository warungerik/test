<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault();
    }
    public function game()
    {
        return $this->belongsTo(Game::class)->withDefault();
    }
    public function denom()
    {
        return $this->belongsTo(Denom::class)->withDefault();
    }
}
