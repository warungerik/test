<?php

namespace App\Http\Controllers;

use App\Libraries\Digiflazz;
use App\Libraries\ToWebp;
use App\Models\Category;
use App\Models\CategoryPPOB;
use App\Models\Config;
use App\Models\Denom;
use App\Models\Feedback;
use App\Models\FlashSale;
use App\Models\Game;
use App\Models\History;
use App\Models\Layanan;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Stock;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();
        $dataHistory = History::where('status', 'success')->get();

        $revenue = $dataHistory->sum('price');
        $revenueMonthly = $dataHistory->where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->sum('price');

        return Inertia::render('admin/dashboard', compact('dataHistory', 'revenue', 'revenueMonthly'));
    }

    public function konfigurasi_website()
    {
        return Inertia::render('admin/konfigurasi/website');
    }
    public  function konfigurasi_order()
    {
        $config = config('website.all');
        $order = $config->konfigurasi_order;
        return Inertia::render('admin/konfigurasi/order', compact('order'));
    }
    public function konfigurasi_banner()
    {
        $config = config('website.all');
        $banner = $config->konfigurasi_banner;
        return Inertia::render('admin/konfigurasi/banner', compact('banner'));
    }
    public function konfigurasi_payment_gateway()
    {
        $config = config('website.all');
        $payment_gateway = $config->konfigurasi_payment;
        return Inertia::render('admin/konfigurasi/payment_gateway', compact('payment_gateway'));
    }
    public function konfigurasi_flash_sale()
    {
        $config = config('website.all');
        $flash_sale = $config->konfigurasi_flashsale;
        $flashsale = FlashSale::with('product', 'game', 'denom')->orderBy('id', 'desc')->get();
        $product = Product::orderBy('name', 'asc')->get();
        $denom = Denom::orderBy('price', 'asc')->get();
        $game = Game::orderBy('name', 'asc')->get();
        return Inertia::render('admin/konfigurasi/flash_sale', compact('flash_sale', 'flashsale', 'product', 'denom', 'game'));
    }

    public function konfigurasi_digiflazz()
    {
        return Inertia::render('admin/konfigurasi/digiflazz');
    }

    public function voucher()
    {
        $product = Product::all();
        return Inertia::render('admin/voucher', compact('product'));
    }

    public function category()
    {
        $category = Category::all();
        return Inertia::render('admin/kategori', compact('category'));
    }
    public function data_providers()
    {
        $provider = Provider::orderBy('id', 'desc')->get();
        return Inertia::render('admin/data/providers', compact('provider'));
    }
    public function data_products()
    {
        $game = Game::orderBy('id', 'desc')->get();
        $product = Product::with('category', 'game', 'provider')->where('ppob', '0')->orderBy('id', 'desc')->get();
        $providers = Provider::orderBy('id', 'desc')->get();
        $categories = Category::orderBy('id', 'desc')->get();
        return Inertia::render('admin/data/products', compact('game', 'product', 'providers', 'categories'));
    }
    public function data_payment()
    {
        $config = config('website.all');
        $categories = PaymentCategory::orderBy('id', 'desc')->get();
        $payments = Payment::with('category')->orderBy('id', 'desc')->get();
        $kPayment = $config->konfigurasi_payment;
        return Inertia::render('admin/data/payment', compact('payments', 'categories', 'kPayment'));
    }
    public function data_denom()
    {
        $denom = Denom::with('product')->orderBy('id', 'desc')->get();
        $products = Product::orderBy('id', 'desc')->get();
        return Inertia::render('admin/data/denom', compact('denom', 'products'));
    }
    // ---------------- [PPOB] --------------------
    public function data_products_ppob()
    {
        $product = Product::with('category', 'game', 'provider')->where('ppob', '1')->orderBy('id', 'desc')->get();
        $providers = Provider::orderBy('id', 'desc')->get();
        $categories = Category::orderBy('id', 'desc')->get();
        return Inertia::render('admin/data/products-ppob', compact('product', 'providers', 'categories'));
    }
    public function denom_ppob()
    {
        $category = CategoryPPOB::all();
        $product = Product::with('provider', 'game', 'category')->where('ppob', '1')->get();
        return Inertia::render('admin/data/denom-ppob', compact('category', 'product'));
    }
    public function feedback()
    {
        $feedback = Feedback::orderBy('id', 'desc')->get();
        return Inertia::render('admin/feedback', compact('feedback'));
    }
    public function history()
    {
        $history = History::orderBy('id', 'desc')->get();
        return Inertia::render('admin/history', compact('history'));
    }
    public function stock_license()
    {
        $stock = Stock::with('product', 'denom')->orderBy('id', 'desc')->get();
        $products = Product::orderBy('id', 'desc')->get();
        $denoms = Denom::orderBy('name', 'asc')->get();
        return Inertia::render('admin/stock_license', compact('stock', 'products', 'denoms'));
    }
    // ------------------- [API] -------------------

    public function submit_voucher(Request $request)
    {
        if ($request->type_action == 'tambah') {
            foreach ($request->all() as $key => $value) {
                if ($value == null && $value != 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data tidak boleh kosong '
                    ]);
                }
            }
            $voucher = Voucher::where('code', $request->code)->first();
            if ($voucher) {
                return response()->json(['status' => false, 'message' => 'Kode Voucher sudah ada']);
            }
            $data = [];
            foreach ($request->all() as $key => $value) {
                $data[$key] = $value;
            }
            Voucher::create($data);
            return response()->json(['status' => true, 'message' => 'Berhasil menambah voucher']);
        } elseif ($request->type_action == 'edit') {
            $data = $request->except(['created_at', 'updated_at', 'type_action', 'id']);
            $voucher = Voucher::find($request->id);
            if (!$voucher) {
                return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan']);
            }
            $voucher->update($data);
            return response()->json(['status' => true, 'message' => 'Berhasil mengubah voucher']);
        } else {
            $voucher = Voucher::find($request->id);
            if (!$voucher) {
                return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan']);
            }
            $voucher->delete();
            return response()->json(['status' => true, 'message' => 'Berhasil menghapus voucher']);
        }
    }
    public function voucher_detail(Request $request)
    {
        $voucher = Voucher::find($request->id);
        if (!$voucher) {
            return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan']);
        }
        return response()->json(['status' => true, 'message' => 'Berhasil menampilkan voucher', 'data' => $voucher]);
    }
    public function submit_konfigurasi_website(Request $request)
    {
        $config = Config::first();
        $oldConfig = json_decode($config->website, true);

        $data = $request->except(['logo', 'favicon', 'header', 'footer', 'og_image', 'cover_image']);
        $data['header'] = $request->input('header', []);
        $data['footer'] = $request->input('footer', []);
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/logo', $filename, 'public');

            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            if (File::exists($oldConfig['logo'])) {
                File::delete($oldConfig['logo']);
            }
            $data['logo'] = $fullPath;
        } else {
            $data['logo'] = $oldConfig['logo'] ?? null;
        }
        if ($request->hasFile('header.favicon')) {
            $file = $request->file('header.favicon');

            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/favicon', $filename, 'public');

            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;

            if (File::exists($oldConfig['header']['favicon'])) {
                File::delete($oldConfig['header']['favicon']);
            }
            $data['header']['favicon'] = $fullPath;
        } else {
            $data['header']['favicon'] = $oldConfig['header']['favicon'] ?? null;
        }

        if ($request->hasFile('header.og.image.url')) {
            $file = $request->file('header.og.image.url');

            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/icon', $filename, 'public');

            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            if (File::exists($oldConfig['header']['og']['image']['url'])) {
                File::delete($oldConfig['header']['og']['image']['url']);
            }
            $data['header']['og']['image']['url'] = $fullPath;
        } else {
            $data['header']['og']['image']['url'] = $oldConfig['header']['og']['image']['url'] ?? null;
        }

        if ($request->hasFile('footer.section3.image_cover')) {
            $file = $request->file('footer.section3.image_cover');

            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/banner', $filename, 'public');

            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            if (File::exists($oldConfig['footer']['section3']['image_cover'])) {
                File::delete($oldConfig['footer']['section3']['image_cover']);
            }
            $data['footer']['section3']['image_cover'] = $fullPath;
        } else {
            $data['footer']['section3']['image_cover'] = $oldConfig['footer']['section3']['image_cover'] ?? null;
        }

        $config->website = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $config->save();
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah konfigurasi']);
    }
    public function submit_konfigurasi_order(Request $request)
    {
        $config = config('website.all');
        $config->konfigurasi_order = $request->all();
        $config->save();
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah konfigurasi']);
    }
    public function tambah_banner(Request $request)
    {
        $config = config('website.all');
        $fileSize = $request->file('banner')->getSize();
        if ($fileSize > 2048 * 1024) {
            return response()->json([
                'status' => false,
                'message' => 'Ukuran file terlalu besar, maksimal 2MB'
            ]);
        }
        $file = $request->file('banner');
        $filename = time() . '.' . $file->getClientOriginalExtension();

        $pictPath = $file->storeAs('assets/images/banner', $filename, 'public');

        $toWebp = new ToWebp();
        $webp = $toWebp->convert($pictPath, 70, true);
        $fullPath = $webp->file ?? $filename;

        $configBanner = array_values((array) $config->konfigurasi_banner ?? []);
        $configBanner[] = $fullPath;

        $config->konfigurasi_banner = $configBanner;
        $config->save();

        return response()->json([
            'status' => true,
            'path' => $fullPath,
            'message' => 'Banner berhasil ditambahkan'
        ]);
    }

    public function hapus_banner(Request $request)
    {
        $config = config('website.all');
        $configBanner = array_values((array) $config->konfigurasi_banner ?? []);

        if (isset($configBanner[$request->id])) {
            $path = $configBanner[$request->id];

            if (File::exists($path)) {
                File::delete($path);
            }
            unset($configBanner[$request->id]);
            $configBanner = array_values($configBanner);
        }
        $config->konfigurasi_banner = $configBanner;
        $config->save();

        return response()->json([
            'status' => true,
            'message' => 'Banner berhasil dihapus'
        ]);
    }

    public function submit_payment_gateway(Request $request)
    {
        $config = config('website.all');
        $id = $request->id;
        $except = $request->except(['id']);
        $data = $config->konfigurasi_payment;
        $data[$id] = $except;
        $config->konfigurasi_payment = $data;
        $config->save();
        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengubah konfigurasi'
        ]);
    }
    public function submit_flash_sale(Request $request)
    {
        if ($request->create == '0') {
            $flashsale = FlashSale::with('game', 'product', 'denom')->find($request->id);
            if (!$flashsale) {
                return response()->json(['status' => false, 'message' => 'Flash sale tidak ditemukan']);
            }
            $except = $request->except(['id']);
            $flashsale->update($except);
            return response()->json(['status' => true, 'message' => 'Berhasil mengubah flash sale', 'data' => $flashsale]);
        } else {
            $fs = FlashSale::with('game', 'product', 'denom')->where('denom_id', $request->denom_id)->first();
            if ($fs) {
                return response()->json(['status' => false, 'message' => 'Flash sale sudah ada']);
            }
            $flashsale = FlashSale::create($request->all());
            $flashsale = FlashSale::with('game', 'product', 'denom')->find($flashsale->id);
            return response()->json(['status' => true, 'message' => 'Berhasil menambahkan flash sale', 'data' => $flashsale]);
        }
    }
    public function submit_konfigurasi_flash_sale(Request $request)
    {
        $config = config('website.all');
        $decode = $config->konfigurasi_flash_sale;
        $decode['expired_at'] = $request->expired_at;
        $decode['status'] = $request->status;
        $config->konfigurasi_flashsale = $decode;
        $config->save();
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah konfigurasi flash sale']);
    }
    public function delete_flash_sale(Request $request)
    {
        $flashsale = FlashSale::with('game', 'product', 'denom')->find($request->id);
        if (!$flashsale) {
            return response()->json(['status' => false, 'message' => 'Flash sale tidak ditemukan']);
        }
        $flashsale->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus flash sale']);
    }

    public function change_ppob(Request $request)
    {
        $config = config('website.all');
        $ppob = $config->konfigurasi_ppob;
        $all = $request->all();
        foreach ($all as $key => $val) {
            if (isset($ppob[$key])) {
                $ppob[$key] = $val == '1' ? true : ($val == '0' ? false : $val);
            }
        }
        $config->konfigurasi_ppob = $ppob;
        $config->save();
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah data']);
    }

    public function change_profit(Request $request)
    {
        $config = config('website.all');
        $config->konfigurasi_profit = $request->data;
        $config->save();
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah data']);
    }

    public function cek_balance()
    {
        $config = config('website.all');
        $ppob = $config->konfigurasi_ppob;
        $digiflazz = new Digiflazz();
        $data = $digiflazz->balance();
        if (isset($data['data']['deposit'])) {
            $ppob['balance'] = (int) $data['data']['deposit'];
            $config->konfigurasi_ppob = $ppob;
            $config->save();
            return response()->json(['status' => true, 'message' => 'Berhasil mengambil data', 'balance' => $data['data']['deposit']]);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal mengambil data saldo']);
        }
    }

    public function provider_store(Request $request)
    {
        if ($request->create == '0') {
            $provider = Provider::find($request->id);
            if (!$provider) {
                return response()->json(['status' => false, 'message' => 'Provider tidak ditemukan']);
            }

            $except = $request->except(['id']);

            if ($request->status == 'inactive' && $request->status != $provider->status) {
                Product::where('provider_id', $provider->id)->update(['status' => 'inactive']);
            } elseif ($request->status == 'active' && $request->status != $provider->status) {
                Product::where('provider_id', $provider->id)->update(['status' => 'active']);
            }
            $currentUrl = $provider->url ?? [];
            $newUrl = is_array($request->url) ? $request->url : json_decode($request->url, true) ?? [];

            $except['url'] = array_merge($currentUrl, $newUrl);

            $provider->update($except);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mengubah provider',
                'data' => $provider
            ]);
        } elseif ($request->create == '1') {
            $provider = Provider::where('name', $request->name)->first();
            if ($provider) {
                return response()->json(['status' => false, 'message' => 'Provider sudah ada']);
            }
            $provider = Provider::create($request->all());
            $latest = Provider::latest()->first();
            return response()->json(['status' => true, 'message' => 'Berhasil menambahkan provider', 'data' => $latest]);
        } else {
            $provider = Provider::find($request->id);
            if (!$provider) {
                return response()->json(['status' => false, 'message' => 'Provider tidak ditemukan']);
            }
            $provider->delete();
            return response()->json(['status' => true, 'message' => 'Berhasil menghapus provider']);
        }
    }
    public function product_update(Request $request, $id)
    {
        $product = Product::with('category', 'game', 'provider')->find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product tidak ditemukan']);
        }
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            if ($file->getClientOriginalExtension() == 'webp') {
                return response()->json(['status' => false, 'message' => 'Thumbnail harus berformat jpg/png atau selain webp']);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/product', $filename, 'public');
            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            $pathThumbnail =  $fullPath;
            $product->thumbnail = $pathThumbnail;
            if (File::exists('assets/images/product/' . $product->thumbnail)) {
                File::delete('assets/images/product/' . $product->thumbnail);
            }
        }
        if ($request->hasFile('banner')) {
            $file = $request->file('banner');
            if ($file->getClientOriginalExtension() == 'webp') {
                return response()->json(['status' => false, 'message' => 'Banner harus berformat jpg/png atau selain webp']);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/banner', $filename, 'public');
            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            $pathBanner = $fullPath;
            $product->banner = $pathBanner;
            if (File::exists('assets/images/banner/' . $product->banner)) {
                File::delete('assets/images/banner/' . $product->banner);
            }
        }
        $product->update($request->except(['thumbnail', 'banner', 'id', '_method']));
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah product', 'data' => $product]);
    }

    public function product_store(Request $request)
    {
        $cekslug = Product::where('slug', $request->slug)->first();
        if ($cekslug) {
            return response()->json(['status' => false, 'message' => 'Slug sudah ada']);
        }

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            if ($file->getClientOriginalExtension() == 'webp') {
                return response()->json(['status' => false, 'message' => 'Thumbnail harus berformat jpg/png atau selain webp']);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/product', $filename, 'public');
            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            $pathThumbnail = $fullPath;
        }
        if ($request->hasFile('banner')) {
            $file = $request->file('banner');
            if ($file->getClientOriginalExtension() == 'webp') {
                return response()->json(['status' => false, 'message' => 'Banner harus berformat jpg/png atau selain webp']);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/banner', $filename, 'public');
            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            $pathBanner =  $fullPath;
        }
        $gambar = ['thumbnail' => $pathThumbnail ?? null, 'banner' => $pathBanner ?? null];
        $ppob = ['ppob' => $request->type == 'ppob' ? '1' : '0'];
        $allrequest = $request->all();
        $request = array_merge($allrequest, $gambar);
        $request = array_merge($ppob, $request);
        Product::create($request);
        $data = Product::with('category', 'game')->latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambahkan product', 'data' => $data]);
    }

    public function product_destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product tidak ditemukan']);
        }
        if (File::exists($product->thumbnail)) {
            File::delete($product->thumbnail);
        }
        if (File::exists($product->banner)) {
            File::delete($product->banner);
        }
        $product->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus product']);
    }

    public function category_update(Request $request)
    {
        if ($request->type == 'ppob') {
            $category = CategoryPPOB::find($request->id);
        } else {
            $category = Category::find($request->id);
        }
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category tidak ditemukan']);
        }
        $category->update($request->except(['id']));
        return response()->json(['status' => true, 'message' => 'Berhasil mengupdate data', 'data' => $category]);
    }
    public function category_store(Request $request)
    {
        $category = Category::where('name', $request->name)->first();
        if ($category) {
            return response()->json(['status' => false, 'message' => 'Category sudah terdaftar']);
        }
        Category::create($request->all());
        $data = Category::latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }

    public function category_destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category tidak ditemukan']);
        }
        $category->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus data']);
    }

    public function update_game(Request $request)
    {
        $game = Game::find($request->id);
        if (!$game) {
            return response()->json(['status' => false, 'message' => 'Game tidak terdaftar']);
        }
        $except = $request->except(['id']);
        $game->update($except);
        return response()->json(['status' => true, 'message' => 'Berhasil mengupdate data', 'data' => $game]);
    }
    public function store_game(Request $request)
    {
        $game = Game::where('name', $request->name)->first();
        if ($game) {
            return response()->json(['status' => false, 'message' => 'Game sudah terdaftar']);
        }
        $except = $request->except(['id']);
        Game::create($except);
        $data = Game::latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }
    public function game_destroy($id)
    {

        $game = Game::find($id);
        if (!$game) {
            return response()->json(['status' => false, 'message' => 'Game tidak ditemukan']);
        }
        $game->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus game']);
    }

    public function payment_categories_update(Request $request, $id)
    {
        $categories =  PaymentCategory::find($id);
        if (!$categories) {
            return response()->json(['status' => false, 'message' => 'Kategori tidak ditemukan']);
        }
        $except = $request->except(['id']);
        $categories->update($except);
        return response()->json(['status' => true, 'message' => 'Berhasil mengupdate data', 'data' => $categories]);
    }
    public function payment_categories_store(Request $request)
    {
        $categories =  PaymentCategory::where('name', $request->name)->first();
        if ($categories) {
            return response()->json(['status' => false, 'message' => 'Kategori sudah terdaftar']);
        }
        PaymentCategory::create($request->all());
        $data = PaymentCategory::latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }

    public function payment_categories_destroy($id)
    {
        $categories =  PaymentCategory::find($id);
        if (!$categories) {
            return response()->json(['status' => false, 'message' => 'Kategori tidak ditemukan']);
        }
        $categories->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus kategori']);
    }
    public function payment_update(Request $request, $id)
    {
        $payment =  Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Kategori tidak ditemukan']);
        }
        $except = $request->except(['id']);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file->getClientOriginalExtension() == 'webp') {
                return response()->json(['status' => false, 'message' => 'Banner harus berformat jpg/png atau selain webp']);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/payment', $filename, 'public');
            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            $pathPayment =  $fullPath;
            $except['image'] = $pathPayment;
        } else {
            $except['image'] = $except['image_url'];
        }
        if (File::exists('assets/images/payment/' . $payment->image)) {
            File::delete('assets/images/payment/' . $payment->image);
        }
        $payment->update($except);
        return response()->json(['status' => true, 'message' => 'Berhasil mengupdate data', 'data' => $payment]);
    }
    public function payment_store(Request $request)
    {
        $payment =  Payment::where('name', $request->name)->first();
        if ($payment) {
            return response()->json(['status' => false, 'message' => 'Kategori sudah terdaftar']);
        }
        $data = $request->all();
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file->getClientOriginalExtension() == 'webp') {
                return response()->json(['status' => false, 'message' => 'Banner harus berformat jpg/png atau selain webp']);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $pictPath = $file->storeAs('assets/images/payment', $filename, 'public');
            $toWebp = new ToWebp();
            $webp = $toWebp->convert($pictPath, 70, true);
            $fullPath = $webp->file ?? $filename;
            $pathPayment =  $fullPath;
            $data['image'] = $pathPayment;
        } else {
            $data['image'] = $data['image_url'];
        }
        Payment::create($data);
        $data = Payment::latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }

    public function payment_destroy($id)
    {
        $payment =  Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Kategori tidak ditemukan']);
        }
        $payment->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus kategori']);
    }

    public function denom_update(Request $request)
    {
        $denom = Denom::find($request->id);
        if (!$denom) {
            return response()->json(['status' => false, 'message' => 'Denom tidak ditemukan']);
        }
        $except = $request->except(['id']);
        $denom->update($except);
        return response()->json(['status' => true, 'message' => 'Berhasil mengupdate data', 'data' => $denom]);
    }
    public function denom_store(Request $request)
    {
        $denom = Denom::with('product')->where([['name', $request->name], ['product_id', $request->product_id]])->first();
        if ($denom) {
            return response()->json(['status' => false, 'message' => 'Denom sudah terdaftar']);
        }
        Denom::create($request->all());
        $data = Denom::with('product')->latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }
    public function denom_destroy($id)
    {
        $denom = Denom::find($id);
        if (!$denom) {
            return response()->json(['status' => false, 'message' => 'Denom tidak ditemukan']);
        }
        $denom->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus denom']);
    }

    public function get_denom_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'search' => 'string|max:255',
                'sort_by' => 'string|in:id,code,name,category,brand,provider,modal,price,status,created_at,updated_at',
                'sort_order' => 'string|in:asc,desc',
                'status' => 'string|in:active,inactive',
                'category' => 'string|max:255',
                'provider' => 'string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search', '');
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $status = $request->get('status', '');
            $category = $request->get('category', '');
            $provider = $request->get('provider', '');

            $query = Layanan::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%")
                        ->orWhere('category', 'LIKE', "%{$search}%")
                        ->orWhere('brand', 'LIKE', "%{$search}%")
                        ->orWhere('provider', 'LIKE', "%{$search}%")
                        ->orWhere('note', 'LIKE', "%{$search}%");
                });
            }
            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($category)) {
                $query->where('category', $category);
            }

            if (!empty($provider)) {
                $query->where('provider', $provider);
            }

            $query->orderBy($sortBy, $sortOrder);

            $result = $query->paginate($perPage, ['*'], 'page', $page);
            $responseData = [
                'data' => $result->items(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
                'has_more_pages' => $result->hasMorePages(),
                'path' => $result->path(),
                'links' => [
                    'first' => $result->url(1),
                    'last' => $result->url($result->lastPage()),
                    'prev' => $result->previousPageUrl(),
                    'next' => $result->nextPageUrl(),
                ]
            ];
            $distinctCategory = Layanan::distinct()->get('category');
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diambil',
                'data' => $responseData,
                'category' => $distinctCategory
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function update_denom_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:layanans,id',
                'code' => 'required|string|max:100|unique:layanans,code,' . $request->id,
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'provider' => 'required|string|max:100',
                'brand' => 'nullable|string|max:100',
                'type' => 'nullable|string',
                'modal' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'multi' => 'nullable|integer|in:0,1',
                'note' => 'nullable|string|max:500',
                'server' => 'nullable|integer|min:0',
                'offline' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }
            $layanan = Layanan::findOrFail($request->id);

            $layanan->update([
                'product_id' => $request->get('product_id', $layanan->product_id),
                'category_id' => $request->get('category_id', $layanan->category_id),
                'code' => $request->code,
                'name' => $request->name,
                'category' => $request->category,
                'provider' => $request->provider,
                'brand' => $request->get('brand', $layanan->brand),
                'type' => $request->get('type', $layanan->type),
                'modal' => $request->modal,
                'price' => $request->price,
                'multi' => $request->get('multi', $layanan->multi),
                'note' => $request->get('note', $layanan->note),
                'server' => $request->get('server', $layanan->server),
                'offline' => $request->get('offline', $layanan->offline),
                'status' => $request->get('status', $layanan->status)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $layanan->fresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function add_denom_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:100|unique:layanans,code',
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'provider' => 'required|string|max:100',
                'brand' => 'nullable|string|max:100',
                'type' => 'nullable|string|in:Umum,Premium,Spesial',
                'modal' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'multi' => 'nullable|integer|in:0,1',
                'note' => 'nullable|string|max:500',
                'server' => 'nullable|integer|min:0',
                'offline' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive'
            ], [
                'code.required' => 'Kode wajib diisi',
                'code.unique' => 'Kode sudah digunakan',
                'name.required' => 'Nama produk wajib diisi',
                'category.required' => 'Kategori wajib dipilih',
                'provider.required' => 'Provider wajib dipilih',
                'modal.required' => 'Modal wajib diisi',
                'modal.numeric' => 'Modal harus berupa angka',
                'price.required' => 'Harga jual wajib diisi',
                'price.numeric' => 'Harga jual harus berupa angka'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }
            // 
            $layanan = Layanan::create([
                'product_id' => $request->get('product_id', 0),
                'category_id' => $request->get('category_id', 0),
                'code' => $request->code,
                'name' => $request->name,
                'category' => $request->category,
                'provider' => $request->provider,
                'brand' => $request->get('brand', ''),
                'type' => $request->get('type', 'Umum'),
                'modal' => $request->modal,
                'price' => $request->price,
                'multi' => $request->get('multi', 1),
                'note' => $request->get('note', ''),
                'server' => $request->get('server', 0),
                'offline' => $request->get('offline', '00:00 - 00:00'),
                'status' => $request->get('status', 'active')
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $layanan
            ], 201);
        } catch (\Exception $e) {
            // \Log::error('Error in store_denom_ppob: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan data',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function update_status_denom_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:layanans,id',
                'status' => 'required|string|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $layanan = Layanan::findOrFail($request->id);
            $layanan->update(['status' => $request->status]);

            return response()->json([
                'status' => true,
                'message' => 'Status berhasil diperbarui',
                'data' => [
                    'id' => $layanan->id,
                    'status' => $layanan->status,
                    'updated_at' => $layanan->updated_at
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui status',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function update_auto_update_ppob(Request $request)
    {
        $layanan = Layanan::find($request->id);
        if (!$layanan) {
            return response()->json(['status' => false, 'message' => 'Layanan tidak ditemukan']);
        }
        $layanan->auto_update = $request->auto_update;
        $layanan->save();
        return response()->json(['status' => true, 'message' => 'Berhasil update data']);
    }
    public function delete_denom_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:layanans,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $layanan = Layanan::findOrFail($request->id);
            $layanan->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function set_product_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:layanans,id',
                'product_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data ID tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }
            Layanan::whereIn('id', $request->ids)->update(['product_id' => $request->product_id]);
            return response()->json([
                'status' => true,
                'message' => "Berhasil mengubah data"
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
    public function set_group_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:layanans,id',
                'category' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data ID tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }
            $updateData = Layanan::whereIn('id', $request->ids)->update(['category_id' => $request->category]);
            return response()->json([
                'status' => true,
                'message' => "Berhasil mengubah data"
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
    public function bulk_delete_denom_ppob(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:layanans,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data ID tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = Layanan::whereIn('id', $request->ids)->delete();

            return response()->json([
                'status' => true,
                'message' => "Berhasil menghapus {$deletedCount} data",
                'deleted_count' => $deletedCount
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function sinkronisasi_digiflazz()
    {
        $profitData = config('website.all')->konfigurasi_profit;
        $digiflazz = new Digiflazz();
        $service  = $digiflazz->service();
        $serviceMap = collect($service['data'])->keyBy('buyer_sku_code');
        $arrSku = collect();
        $replace = config('website.all')->replace_text;
        $ppob =  config('website.all')->konfigurasi_ppob;
        if ($ppob['auto_add']) {
            $arrSku = collect();
            foreach ($service['data'] as $row) {
                $layanan = Layanan::where('code', $row['buyer_sku_code'])->first();
                $dataProfit = calculateProfitData($row, $profitData, true);
                $createData = [];
                if (!$layanan) {
                    $createData = [
                        'product_id'   => 0,
                        'code'         => $row['buyer_sku_code'],
                        'provider'     => 'digiflazz',
                        'name'         => $row['product_name'] ?? '',
                        'category'     => $row['category'] ?? '',
                        'brand'        => $row['brand'] ?? '',
                        'type'         => $row['type'] ?? '',
                        'modal'        => $row['price'] ?? 0,
                        'price_sell'   => 0,
                        'price'        => $dataProfit,
                        'multi'        => !empty($row['multi']) ? '1' : '0',
                        'note'         => $row['desc'] ?? '',
                        'auto_update'  => ['modal', 'price', 'note', 'status'],
                        'status'       => (!empty($row['buyer_product_status']) && !empty($row['seller_product_status'])) ? 'active' : 'inactive',
                    ];
                } else {
                    $auto_update = $layanan->auto_update ?? [];
                    foreach ($auto_update as $field) {
                        switch ($field) {
                            case 'name':
                                $createData['name'] = $row['product_name'] ?? '';
                                break;
                            case 'category':
                            case 'brand':
                            case 'type':
                                $createData[$field] = $row[$field] ?? '';
                                break;
                            case 'modal':
                                $createData['modal'] = $row['price'] ?? 0;
                                break;
                            case 'price':
                                $createData['price'] = $dataProfit;
                                break;
                            case 'multi':
                                $createData['multi'] = !empty($row['multi']) ? '1' : '0';
                                break;
                            case 'note':
                                $createData['note'] = $row['desc'] ?? '';
                                break;
                            case 'status':
                                $createData['status'] = (!empty($row['buyer_product_status']) && !empty($row['seller_product_status'])) ? 'active' : 'inactive';
                                break;
                        }
                    }
                }
                foreach ($replace as $r) {
                    $key = $r['data'];
                    if (isset($createData[$key])) {
                        $createData[$key] = trim(str_replace($r['text'], $r['replace'], $createData[$key]));
                    }
                }
                Layanan::updateOrCreate(
                    ['code' => $row['buyer_sku_code']],
                    $createData
                );
            }
        } else {
            $layanan = Layanan::where('provider', 'digiflazz')->get();
            foreach ($layanan as $row) {
                $matchedService = $serviceMap->get($row->code);
                if (!$matchedService) {
                    continue;
                }
                $dataProfit = calculateProfitData($matchedService, $profitData, true);
                $createData = [];
                $auto_update = $row->auto_update ?? [];
                foreach ($auto_update as $field) {
                    switch ($field) {
                        case 'name':
                            $createData['name'] = $matchedService['product_name'] ?? '';
                            break;
                        case 'category':
                        case 'brand':
                        case 'type':
                            $createData[$field] = $matchedService[$field] ?? '';
                            break;
                        case 'modal':
                            $createData['modal'] = $matchedService['price'] ?? 0;
                            break;
                        case 'price':
                            $createData['price'] = $dataProfit;
                            break;
                        case 'multi':
                            $createData['multi'] = !empty($matchedService['multi']) ? '1' : '0';
                            break;
                        case 'note':
                            $createData['note'] = $matchedService['desc'] ?? '';
                            break;
                        case 'status':
                            $createData['status'] = (!empty($matchedService['buyer_product_status']) && !empty($matchedService['seller_product_status'])) ? 'active' : 'inactive';
                            break;
                    }
                }
                foreach ($replace as $r) {
                    $key = $r['data'];
                    if (isset($createData[$key])) {
                        $createData[$key] = trim(str_replace($r['text'], $r['replace'], $createData[$key]));
                    }
                }
                Layanan::updateOrCreate(
                    ['code' => $matchedService['buyer_sku_code']],
                    $createData
                );
            }
        }
        return response()->json(['status' => true, 'message' => 'Data digiflazz berhasil sinkron']);
    }
    public function delete_replace_text(Request $request)
    {
        $config = config('website.all');
        $replace_text = $config->replace_text;
        $validate = isset($replace_text[$request->id]) ? $replace_text[$request->id] : false;
        if (!$validate) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
        unset($replace_text[$request->id]);
        $config->replace_text = $replace_text;
        $config->save();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus data', 'data' => $config->replace_text]);
    }

    public function update_replace_text(Request $request)
    {
        $config = config('website.all');
        $replace = $config->replace_text;
        $layanan = Layanan::all();
        foreach ($layanan as $row) {
            foreach ($replace as $r) {
                $key = $r['data'];
                $row->$key = trim(str_replace($r['text'], $r['replace'], $row->$key));
            }
            $row->save();
        }
        return response()->json(['status' => true, 'message' => 'Berhasil mengubah data']);
    }

    public function tambah_replace_text(Request $request)
    {
        $config = config('website.all');
        $replace_text = $config->replace_text;

        $replace_text = is_array($replace_text) ? array_values($replace_text) : [];

        $replace_text[] = [
            'text'    => $request->original,
            'replace' => $request->replace,
            'data'    => $request->data
        ];

        $config->replace_text = array_values($replace_text);
        $config->save();

        return response()->json([
            'status'  => true,
            'message' => 'Berhasil menambahkan data',
            'data'    => $config->replace_text
        ]);
    }

    public function history_update(Request $request, $id)
    {
        $history = History::find($id);
        if (!$history) {
            return response()->json(['status' => false, 'message' => 'History tidak ditemukan']);
        }

        $data = $request->all();
        $sellerNotes = $history->seller_notes ?? [];
        $sellerNotes['notes'] = $data['seller_notes']['notes'] ?? '';
        $sellerNotes['download'] = $data['seller_notes']['download'] ?? [];

        $history->seller_notes = $sellerNotes;

        $except = $request->except(['id', 'seller_notes', '_method']);
        $history->update($except);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengupdate data',
            'data' => $history
        ]);
    }
    public function history_destroy($id)
    {
        $history = History::find($id);
        if (!$history) {
            return response()->json(['status' => false, 'message' => 'History tidak ditemukan']);
        }
        $history->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus history']);
    }
    public function stock_license_bulk(Request $request)
    {
        if ($request->product_id == null || $request->denom_id == null) {
            return response()->json(['status' => false, 'message' => 'Product id dan denom tidak boleh kosong']);
        }
        if (!is_array($request->licenses)) {
            return response()->json(['status' => false, 'message' => 'Licenses harus di isi']);
        }
        if (count($request->licenses) == 0) {
            return response()->json(['status' => false, 'message' => 'Licenses harus di isi']);
        }
        foreach ($request->licenses as $license) {
            if ($license == null || $license == '') {
                return response()->json(['status' => false, 'message' => 'Licenses tidak boleh kosong']);
            }
            Stock::create(['product_id' => $request->product_id, 'denom_id' => $request->denom_id, 'license' => $license]);
        }
        $countData = count($request->licenses);
        $data = Stock::with('product', 'denom')->where('product_id', $request->product_id)->where('denom_id', $request->denom_id)->latest()->take($countData)->get();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }
    public function stock_license_store(Request $request)
    {
        $stock = Stock::with('product', 'denom')->where('product_id', $request->product_id)->where('denom_id', $request->denom_id)->where('license', $request->license)->first();
        if ($stock) {
            return response()->json(['status' => false, 'message' => 'Stock sudah terdaftar']);
        }
        Stock::create($request->all());
        $data = Stock::with('product', 'denom')->where('product_id', $request->product_id)->where('denom_id', $request->denom_id)->latest()->first();
        return response()->json(['status' => true, 'message' => 'Berhasil menambah data', 'data' => $data]);
    }
    public function stock_license_update(Request $request, $id)
    {
        $stock = Stock::with('product', 'denom')->where('id', $id)->first();
        if (!$stock) {
            return response()->json(['status' => false, 'message' => 'Stock tidak ditemukan']);
        }
        if ($request->product_id == null || $request->denom_id == null) {
            return response()->json(['status' => false, 'message' => 'Product id dan denom tidak boleh kosong']);
        }
        $except = $request->except(['id']);
        $stock->update($except);
        $latest = $stock->load('product', 'denom');
        return response()->json(['status' => true, 'message' => 'Berhasil mengupdate data', 'data' => $latest]);
    }

    public function stock_license_destroy($id)
    {
        $stock = Stock::with('product', 'denom')->where('id', $id)->first();
        if (!$stock) {
            return response()->json(['status' => false, 'message' => 'Stock tidak ditemukan']);
        }
        $stock->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus stock']);
    }
    public function feedback_reply(Request $request)
    {
        $feedback = Feedback::where('id', $request->id)->first();
        if (!$feedback) {
            return response()->json(['status' => false, 'message' => 'Feedback tidak ditemukan']);
        }
        $feedback->reply_admin = $request->reply;
        $feedback->update();
        return response()->json(['status' => true, 'message' => 'Berhasil memberi reply', 'data' => $feedback]);
    }
    public function feedback_bulk_reply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:feedbacks,id',
            'reply' => 'required|string|max:1000|min:10',
        ], [
            'ids.required' => 'ID feedback harus dipilih',
            'ids.array' => 'Format ID tidak valid',
            'ids.min' => 'Minimal pilih 1 feedback',
            'ids.*.exists' => 'Feedback tidak ditemukan',
            'reply.required' => 'Reply tidak boleh kosong',
            'reply.min' => 'Reply minimal 10 karakter',
            'reply.max' => 'Reply maksimal 1000 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ids = $request->input('ids');
            $reply = trim($request->input('reply'));
            $feedbacksToUpdate = Feedback::whereIn('id', $ids)->get();

            if ($feedbacksToUpdate->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada feedback yang ditemukan'
                ], 404);
            }
            $updated = Feedback::whereIn('id', $ids)
                ->update([
                    'reply_admin' => $reply
                ]);

            if ($updated === 0) {
                throw new \Exception('Gagal mengupdate feedback');
            }
            $updatedFeedbacks = Feedback::whereIn('id', $ids)
                ->get();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Berhasil mengirim reply ke {$updated} feedback",
                'data' => $updatedFeedbacks,
                'meta' => [
                    'total_updated' => $updated,
                    'reply_length' => strlen($reply),
                    'admin_id' => Auth::id(),
                    'timestamp' => now()->toISOString()
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengirim bulk reply',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function feedback_destroy(Request $request)
    {
        $id = $request->input('id');
        $feedback = Feedback::whereIn('id', $id)->get();
        if ($feedback->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Feedback tidak ditemukan']);
        }
        $feedback->each->delete();
        return response()->json(['status' => true, 'message' => 'Berhasil menghapus feedback']);
    }
}
