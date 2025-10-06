<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServerSideController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\isAdmin;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
    Route::get('login', 'login')->name('login');

    Route::prefix('api')->group(function () {
        Route::post('login', 'proses_login')->name('api.login');
    });
});
Route::middleware(Authenticate::class, 'auth.session', isAdmin::class)->group(function () {
    Route::controller(UserController::class)->prefix('user')->group(function () {
        Route::get('settings', 'settings')->name('user.settings');
        Route::get('logout', 'logout')->name('user.logout');

        Route::prefix('api')->group(function () {
            Route::put('update-user-profile', 'update_user_profile')->name('api.user.update-profile');
            Route::post('update-user-password', 'update_user_password')->name('api.user.change-password');
        });
    });
    Route::controller(AdminController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::prefix('konfigurasi')->group(function () {
            Route::get('website', 'konfigurasi_website')->name('admin.konfigurasi.website');
            Route::get('order', 'konfigurasi_order')->name('admin.konfigurasi.order');
            Route::get('banner', 'konfigurasi_banner')->name('admin.konfigurasi.banner');
            Route::get('payment-gateway', 'konfigurasi_payment_gateway')->name('admin.konfigurasi.payment-gateway');
            Route::get('flash-sale', 'konfigurasi_flash_sale')->name('admin.konfigurasi.flash-sale');
            Route::get('digiflazz', 'konfigurasi_digiflazz')->name('admin.konfigurasi.digiflazz');
        });
        Route::get('voucher', 'voucher')->name('admin.voucher');
        Route::get('categories', 'category')->name('admin.categories');
        Route::prefix('data')->group(function () {
            Route::get('providers', 'data_providers')->name('admin.data.providers');
            Route::get('products', 'data_products')->name('admin.data.products');
            Route::get('payment', 'data_payment')->name('admin.data.payment');
            Route::get('denom', 'data_denom')->name('admin.data.denom');
        });
        Route::prefix('ppob')->group(function () {
            Route::get('products', 'data_products_ppob')->name('admin.data-ppob.products');
            Route::get('denom', 'denom_ppob')->name('admin.data-ppob.denom');
        });
        Route::get('feedback', 'feedback')->name('admin.feedback');
        Route::get('history', 'history')->name('admin.history');
        Route::get('stock-license', 'stock_license')->name('admin.stock-license');

        Route::prefix('api')->group(function () {
            Route::post('submit-voucher', 'submit_voucher')->name('api.voucher.store');
            Route::post('voucher-detail', 'voucher_detail')->name('api.voucher.detail');
            Route::post('konfigurasi_website', 'submit_konfigurasi_website')->name('api.konfigurasi-website');
            Route::post('konfigurasi_order', 'submit_konfigurasi_order')->name('api.konfigurasi-order');
            Route::post('tambah-banner', 'tambah_banner')->name('api.tambah-banner');
            Route::post('hapus-banner', 'hapus_banner')->name('api.hapus-banner');

            Route::post('payment-gateway', 'submit_payment_gateway')->name('api.payment-gateway');
            Route::post('flash-sale', 'submit_flash_sale')->name('api.flash-sale');
            Route::post('konfigurasi-flash-sale', 'submit_konfigurasi_flash_sale')->name('api.konfigurasi-flashsale');
            Route::post('delete-flash-sale', 'delete_flash_sale')->name('api.delete-flash-sale');

            Route::post('change-ppob', 'change_ppob')->name('api.change-ppob');
            Route::post('change-profit', 'change_profit')->name('api.change-profit');
            Route::post('cek-balance', 'cek_balance')->name('api.cek-balance');
            Route::post('provider-store', 'provider_store')->name('api.providers.store');

            Route::put('product-update/{id}', 'product_update')->name('api.products.update');
            Route::post('product-store', 'product_store')->name('api.products.store');
            Route::post('product-destroy/{id}', 'product_destroy')->name('api.products.destroy');

            Route::post('category-update', 'category_update')->name('api.category.update');
            Route::post('category-store', 'category_store')->name('api.category.store');
            Route::post('category-destroy/{id}', 'category_destroy')->name('api.category.destroy');

            Route::post('game-update', 'update_game')->name('api.game.update');
            Route::post('game-store', 'store_game')->name('api.game.store');
            Route::post('game-destroy/{id}', 'game_destroy')->name('api.game.destroy');

            Route::put('payment-categories-update/{id}', 'payment_categories_update')->name('api.payment-categories.update');
            Route::post('payment-categories-store', 'payment_categories_store')->name('api.payment-categories.store');
            Route::post('payment-categories-destroy/{id}', 'payment_categories_destroy')->name('api.payment-categories.destroy');

            Route::put('payment-update/{id}', 'payment_update')->name('api.payment.update');
            Route::post('payment-store', 'payment_store')->name('api.payment.store');
            Route::post('payment-destroy/{id}', 'payment_destroy')->name('api.payment.destroy');

            Route::put('denom-update/{id}', 'denom_update')->name('api.denom.update');
            Route::post('denom-store', 'denom_store')->name('api.denom.store');
            Route::delete('denom-destroy/{id}', 'denom_destroy')->name('api.denom.destroy');

            Route::post('get-denom-ppob', 'get_denom_ppob')->name('api.get-denom-ppob');
            Route::post('update-denom-ppob', 'update_denom_ppob')->name('api.update-denom-ppob');
            Route::post('update-status-denom-ppob', 'update_status_denom_ppob')->name('api.update-status-denom-ppob');
            Route::post('update-auto-update-ppob', 'update_auto_update_ppob')->name('api.update-auto-update-settings');
            Route::post('add-denom-ppob', 'add_denom_ppob')->name('api.store-denom-ppob');
            Route::post('delete-denom-ppob', 'delete_denom_ppob')->name('api.delete-denom-ppob');
            Route::post('set-product-ppob', 'set_product_ppob')->name('api.set-product-ppob');
            Route::post('set-group-ppob', 'set_group_ppob')->name('api.set-group-ppob');
            Route::post('bulk-delete-denom-ppob', 'bulk_delete_denom_ppob')->name('api.bulk-delete-denom-ppob');
            Route::post('sinkronisasi-digiflazz', 'sinkronisasi_digiflazz')->name('api.sinkronisasi-digiflazz');
            Route::post('delete-replace-text', 'delete_replace_text')->name('api.delete-replace-text');
            Route::post('update-replace-text', 'update_replace_text')->name('api.update-replace-text');
            Route::post('tambah-replace-text', 'tambah_replace_text')->name('api.tambah-replace-text');

            Route::put('history_update/{id}', 'history_update')->name('api.history.update');
            Route::post('history-destroy/{id}', 'history_destroy')->name('api.history.destroy');

            Route::post('stock-license-bulk', 'stock_license_bulk')->name('api.stock-licenses.bulk');
            Route::post('stock-license-store', 'stock_license_store')->name('api.stock-licenses.store');
            Route::put('stock-license-update{id}', 'stock_license_update')->name('api.stock-licenses.update');
            Route::delete('stock-license-destroy/{id}', 'stock_license_destroy')->name('api.stock-licenses.destroy');

            Route::post('feedback-reply', 'feedback_reply')->name('api.feedback-reply');
            Route::post('feedback-bulk-reply', 'feedback_bulk_reply')->name('api.feedback-bulk-reply');
            Route::post('feedback-destroy', 'feedback_destroy')->name('api.feedback.destroy');
        });
    });
});

Route::controller(ServerSideController::class)->middleware('auth', isAdmin::class)->prefix('server-side')->group(function () {
    Route::post('table-voucher', 'table_voucher')->name('ssr.table-voucher');
    Route::post('table-provider', 'table_provider')->name('ssr.table-provider');
});
