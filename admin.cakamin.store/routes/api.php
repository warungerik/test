<?php

use App\Http\Controllers\CallbackController;
use App\Libraries\Digiflazz;
use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('xx', function () {
    $digiflazz = new Digiflazz();
    $service  = $digiflazz->service();

    $serviceMap = collect($service['data'])->keyBy('buyer_sku_code');
    return $serviceMap;
});
