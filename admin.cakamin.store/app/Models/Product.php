<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];
    protected $table = 'products';
    public function game()
    {
        return $this->belongsTo(Game::class)->withDefault(function ($game) {
            $game->makeHidden(['created_at', 'updated_at']);
        });
    }
    public function category()
    {
        return $this->belongsTo(Category::class)->withDefault(function ($game) {
            $game->makeHidden(['created_at', 'updated_at']);
        });
    }
    public function provider()
    {
        return $this->belongsTo(Provider::class)->withDefault(function ($game) {
            $game->makeHidden(['created_at', 'updated_at']);
        });
    }
}
