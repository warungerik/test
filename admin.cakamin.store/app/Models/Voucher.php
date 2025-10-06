<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'amount' => 'double:2'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault();
    }
    public static function search($search)
    {
        $src = trim($search);
        return empty($src) ? static::query()
            : static::query()->where('amount', 'like', '%' . $src . '%')
            ->orWhere('code', 'like', '%' . $src . '%')
            ->orWhere('limit', 'like', '%' . $src . '%')
            ->orWhereHas('product', function ($q) use ($src) {
                $q->where('name', 'like', '%' . $src . '%');
            });
    }
}
