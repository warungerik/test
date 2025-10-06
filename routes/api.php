<?php

use App\Http\Controllers\CallbackController;
use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(CallbackController::class)->prefix('callback')->group(function () {
    Route::post('tokopay', 'callback_tokopay')->name('callback.tokopay');
});
Route::get('convert', function () {
    $layanan = Layanan::all();
    foreach ($layanan as $row) {
        $firstArray = array_key_first($row->price);
        $row->price = $row->price[$firstArray];
        $row->save();
    }
});
