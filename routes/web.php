<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;


Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home.index');
    Route::get('product/{product:slug}', 'product')->name('home.product');
    Route::get('invoice/{invoice_id}', 'invoice')->name('home.invoice');
    Route::prefix('page')->group(function () {
        Route::get('daftar-harga', 'daftar_harga')->name('home.daftar-harga');
        Route::get('lacak-transaksi', 'lacak_transaksi')->name('home.lacak-transaksi');
        Route::get('kalkulator', 'kalkulator')->name('home.kalkulator');
        Route::get('reset-member', 'reset_member')->name('home.reset-member');
        Route::get('feedback', 'feedback')->name('home.feedback');
    });
    Route::prefix('api')->group(function () {
        Route::post('data-footer', 'data_footer')->name('api.data-footer');
        Route::post('get-history', 'get_history')->name('api.get-history');
        Route::post('search', 'search')->name('api.search');
        Route::post('select-denom', 'select_denom')->name('api.select-denom');
        Route::post('apply-voucher', 'apply_voucher')->name('api.apply-voucher');
        Route::post('create-order', 'create_order')->name('api.create-order');
        Route::post('submit-feedback', 'submit_feedback')->name('api.submit-feedback');

        Route::post('check-status', 'check_status')->name('api.cek-status');

        Route::post('load-more-feedback', 'load_more_feedback')->name('api.load-more-feedback');
        Route::post('reply-admin', 'reply_admin')->name('api.reply-admin');

        Route::post('reset-license', 'reset_license')->name('api.reset-license');
    });
});
