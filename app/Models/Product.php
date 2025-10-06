<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];
    protected $table = 'products';

    protected $casts = [
        'sistem_target' => 'array'
    ];
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

    public function target()
    {
        return $this->belongsTo(SistemTarget::class, 'target_id', 'id')->withDefault(function ($target) {
            $target->makeHidden(['created_at', 'updated_at']);
        });
    }
}
