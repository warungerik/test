<?php

namespace App\Http\Controllers;

use App\Libraries\Apiv1;
use App\Libraries\Apiv2;
use App\Libraries\Tokopay;
use App\Models\Category;
use App\Models\CategoryPPOB;
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
use App\Models\SistemTarget;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $title = config('website.config')->header->title;
        $flashsale = FlashSale::with('game', 'product', 'denom')->where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        $popular = Product::with(['game', 'category'])->where('status', 'active')->where('popular', 1)->get()->makeHidden(['created_at', 'updated_at']);
        $categories = Category::where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        $product = Product::with(['game', 'category'])->where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        return Inertia::render('home/index', compact('flashsale', 'popular', 'categories', 'product', 'title'));
    }
    public function product($slug)
    {
        $product = Product::with('game', 'category')->where('slug', $slug)->first()->makeHidden(['created_at', 'updated_at', 'cgame_id', 'provider_id', 'game_id', 'target_id']);
        if (!$product) {
            return abort(404);
        }
        $feedback = Feedback::where('information->product->product_id', $product->id)->orderBy('id', 'desc')->limit(5)->get()->makeHidden(['created_at', 'updated_at']);
        $rating = round(Feedback::where('information->product->product_id', $product->id)->avg('rating'), 2);
        $count  = Feedback::where('information->product->product_id', $product->id)->count();
        if ($count > 1000) {
            $c = $count / 1000 . 'rb';
        } else {
            $c = $count;
        }

        $ratings = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $ratings[$star] = Feedback::where('information->product->product_id', (string) $product->id)
                ->where('rating', $star)
                ->count();
            if ($ratings[$star] > 1000) {
                $ratings[$star] = $ratings[$star] / 1000 . 'rb';
            }
        }
        $payment = Payment::with('category')->where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        $paymentCategory = PaymentCategory::where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        $flashsale = FlashSale::where([['game_id', $product->game_id], ['product_id', $product->id]])->get()->makeHidden(['created_at', 'updated_at']);
        $title = 'Order ' . $product->game->name;
        if ($product->ppob == '0') {
            $dataDenom = Denom::where([['product_id', $product->id], ['status', 'active']])->orderBy('price', 'asc')->get()->makeHidden(['created_at', 'updated_at']);
            return Inertia::render('home/product', compact('product', 'feedback', 'rating', 'count', 'c', 'ratings', 'payment', 'paymentCategory', 'dataDenom', 'flashsale', 'title'));
        } else {
            $dataDenom = Layanan::with('categories')->where([['product_id', $product->id], ['status', 'active']])->orderBy('price', 'asc')->get()->makeHidden(['created_at', 'updated_at', 'provider', 'product_id', 'auto_update', 'server', 'price_sell', 'offline', 'modal', 'type', 'category', 'code']);
            $sistem_target = SistemTarget::where('id', $product->target_id)->first()->konfigurasi ?? [];
            $categories = $dataDenom
                ->map(fn($d) => [
                    'id'   => $d->categories->id ?? null,
                    'name' => $d->categories->name ?? null,
                    'icon' => $d->categories->icon ?? null,
                ])
                ->filter(fn($c) => $c['id'] !== null)
                ->unique('id')
                ->sortBy('id')
                ->values();
            return Inertia::render('home/product-ppob', compact('product', 'categories', 'sistem_target', 'feedback', 'rating', 'count', 'c', 'ratings', 'payment', 'paymentCategory', 'dataDenom', 'flashsale', 'title'));
        }
    }

    public function invoice($invoice_id)
    {
        $history = History::where('invoice_id', $invoice_id)->firstOrFail();
        if ($history->expired_at < now() && $history->status == 'pending') {
            $history->status = 'failed';
            $history->save();
        }
        $title = 'Invoice #' . $history->invoice_id;
        $historyFeedback = Feedback::where('information->invoice_id', (string) $history->invoice_id)->first();
        return Inertia::render('home/invoice', compact('history', 'title', 'historyFeedback'));
    }
    // ------------------- [PAGE] -------------------
    public function daftar_harga(Request $request)
    {
        $game = Game::where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        $product = Product::where('status', 'active')->get()->makeHidden(['created_at', 'updated_at', 'download']);
        $denom = Denom::where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        return Inertia::render('home/daftar_harga', compact('game', 'product', 'denom'));
    }
    public function lacak_transaksi(Request $request)
    {
        return Inertia::render('home/lacak_transaksi');
    }
    public function kalkulator(Request $request)
    {
        return Inertia::render('home/kalkulator');
    }

    public function reset_member(Request $request)
    {
        $provider = Provider::where([
            ['status', 'active'],
            ['reset_license', 'enabled']
        ])
            ->get()
            ->makeHidden(['created_at', 'updated_at', 'api_key', 'type_api', 'url', 'name']);

        $product = Product::where('status', 'active')
            ->whereIn('provider_id', $provider->pluck('id'))
            ->select('name', 'id')
            ->get();
        return Inertia::render('home/reset_member', compact('product'));
    }

    public function feedback()
    {
        $feedback = Feedback::orderBy('id', 'desc')->select('id', 'message', 'reply_admin', 'rating', 'information', 'created_at')->limit(3)->get()->makeHidden(['updated_at']);
        $countFeedback = Feedback::count();
        $averageRating = number_format(Feedback::avg('rating'), 1);
        return Inertia::render('home/feedback', compact('feedback', 'countFeedback', 'averageRating'));
    }

    // ------------------- [API] -------------------
    public function data_footer()
    {
        $feedback = Feedback::orderBy('id', 'desc')->take(10)->get()->makeHidden(['updated_at']);
        return response()->json(['status' => true, 'feedback' => $feedback]);
    }
    public function get_history()
    {
        $histories = History::select('invoice_id', 'product')->orderBy('id', 'desc')->limit(10)->get();

        $data = $histories->map(function ($h) {
            $productId = $h->product['product_id'] ?? null;
            $product = $productId ? Product::find($productId, ['id', 'name', 'thumbnail']) : null;

            return [
                'invoice_id' => $h->invoice_id,
                'product'    => $h->product,
                'product_db' => $product,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function search(Request $request)
    {
        $product = Product::with('game')->where('name', 'like', '%' . $request->search . '%')->where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['status' => true, 'data' => $product], 200);
    }
    public function select_denom(Request $request)
    {
        $denom = Denom::where('id', $request->id)->first();
        if (!$denom) {
            $denom = Layanan::where('id', $request->id)->first();
            if (!$denom) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nominal tidak ditemukan'
                ]);
            }
        }
        $payment = Payment::where('status', 'active')->get()->makeHidden(['created_at', 'updated_at']);
        $flashsale = FlashSale::where('denom_id', $request->id)->first();
        if ($flashsale) {
            if ($flashsale->type_discount == 'fixed') {
                $denom->price = $denom->price - $flashsale->discount;
            } else {
                $denom->price = $denom->price - ($denom->price * $flashsale->discount) / 100;
            }
        }
        $data = [];
        foreach ($payment as $row) {
            $fee_fixed = $denom->price + $row->fee_fixed;
            $fee_percent = ($denom->price * $row->fee_percent) / 100;
            $total = $fee_fixed + $fee_percent;
            $data[] = [
                'payment_id' => $row->id,
                'fee' => $total,
                'payment_name' => $row->name
            ];
        }
        return response()->json(['status' => true, 'data' => $data]);
    }
    public function apply_voucher(Request $request)
    {
        $payment = Payment::find($request->payment_id);
        $denom   = Denom::find($request->denom_id);

        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Pembayaran tidak ditemukan']);
        }
        if (!$denom) {
            $denom = Layanan::find($request->denom_id);
            if (!$denom) {
                return response()->json(['status' => false, 'message' => 'Denom tidak ditemukan']);
            }
        }
        $flashsale = FlashSale::where('denom_id', $request->denom_id)->first();
        if ($flashsale) {
            if ($flashsale->type_discount == 'fixed') {
                $denom->price = $denom->price - $flashsale->discount;
            } else {
                $denom->price = $denom->price - ($denom->price * $flashsale->discount) / 100;
            }
        }
        $fee_fixed   = $denom->price + $payment->fee_fixed;
        $fee_percent = ($denom->price * $payment->fee_percent) / 100;
        $total_fee   = ($fee_fixed + $fee_percent) - $denom->price;
        $discount    = 0;
        $finalPrice  = $denom->price + $total_fee;

        $voucher = Voucher::where('code', $request->voucher)->first();

        if ($voucher) {
            if ($voucher->limit > 0 && $voucher->expired_at >= now() && $voucher->minimal <= $denom->price) {

                $discount = match ($voucher->type) {
                    'fixed'   => $voucher->amount,
                    'percent' => $denom->price * ($voucher->nominal / 100),
                    default   => 0,
                };
                $discount = min($discount, $voucher->maximal_fee);

                $afterDiscount = max(0, $denom->price - $discount);
                $finalPrice    = $afterDiscount + $total_fee;
                return response()->json([
                    'status'      => $voucher ? true : false,
                    'message'     => $voucher ? 'Voucher berhasil diterapkan.' : 'Voucher tidak berlaku, harga normal digunakan.',
                    'discount'    => (int) ceil($discount),
                    'final_price' => (int) ceil($finalPrice),
                ]);
            } else {
                return response()->json(['status' => false, 'message' => 'Voucher tidak valid', 'discount' => $discount, 'final_price' => $finalPrice]);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan', 'discount' => $discount, 'final_price' => $finalPrice]);
        }
    }

    public function create_order(Request $request)
    {
        $config = config('website.all');
        $order = $config->konfigurasi_order;
        $website = config('website.config');
        $denom = Denom::with('product')->where('id', $request->denom_id)->first();
        $payment = Payment::with('category')->where('id', $request->payment_id)->first();
        if (!$denom) {
            $denom = Layanan::with('product')->where('id', $request->denom_id)->first();
            if (!$denom) {
                return response()->json(['status' => false, 'message' => 'Nominal tidak ditemukan']);
            }
        }
        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Pembayaran tidak ditemukan']);
        }
        $game = Game::where('id', $denom->product->game_id)->first();
        if (!$game) {
            return response()->json(['status' => false, 'message' => 'Game tidak ditemukan']);
        }
        $flashsale = FlashSale::where('denom_id', $denom->id)->first();
        if ($flashsale) {
            if ($flashsale->type_discount == 'fixed') {
                $denom->price = $denom->price - $flashsale->discount;
            } else {
                $denom->price = $denom->price - ($denom->price * $flashsale->discount) / 100;
            }
        }
        $fee_fixed   = $denom->price + $payment->fee_fixed;
        $fee_percent = ($denom->price * $payment->fee_percent) / 100;
        $total_fee   = round(($fee_fixed + $fee_percent) - $denom->price);
        $discount    = 0;
        $finalPrice  = $denom->price + $total_fee;


        $voucher = Voucher::where('code', $request->voucher)->first();
        if ($voucher) {
            if ($voucher->limit > 0 && $voucher->expired_at >= now() && $voucher->minimal <= $denom->price) {

                $discount = match ($voucher->type) {
                    'fixed'   => $voucher->amount,
                    'percent' => $denom->price * ($voucher->nominal / 100),
                    default   => 0,
                };
                $discount = (int) min($discount, $voucher->maximal_fee);

                $afterDiscount = max(0, $denom->price - $discount);
                $finalPrice    = $afterDiscount + $total_fee;
                $voucher->decrement('limit', 1);
                $voucher->increment('use_limit', 1);
            }
        }
        $invoice_id = str_replace(' ', '', $order['prefix_order']) . random($order['length_random_order'], $order['string']);
        $dataPayment = ['product' => $payment->provider, 'category' => $payment->category->name, 'code' => $payment->code_payment, 'name' => $payment->name, 'fee_fixed' => $payment->fee_fixed, 'fee_percent' => $payment->fee_percent, 'number' => $payment->provider == 'private' ? $payment->number : null, 'name_account' => $payment->provider == 'private' ? $payment->name_account : null];

        if ($payment->provider == 'tokopay') {
            $fullUrl = url()->current();
            $parsedUrl = parse_url($fullUrl);
            $urlWithoutScheme = $parsedUrl['host'];
            $data = [
                'kode_channel' => $payment->code_payment,
                'reff_id' => $invoice_id,
                'amount' => (int) $finalPrice,
                'customer_name' => 'Customer ' . config('website.config')->name_store,
                'customer_email' => 'official@' . $urlWithoutScheme,
                'customer_phone' => $request->whatsapp,
                'redirect_url' => route('home.invoice', $invoice_id),
                'expired_ts' => Carbon::now()->addMinutes((int) $order['exp_order'])->timestamp,
                'items' => [
                    'product_code' => $invoice_id,
                    'name' => 'Order Product Nominal : Rp ' . format_price($finalPrice),
                    'product_url' => url('/'),
                    'price' => (int) $finalPrice,
                    'image_url' => url($product->image ?? $website->logo)
                ],
            ];
            $tokopay = new Tokopay();
            $invoice = $tokopay->createAdvanceOrder($data);
            if (isset($invoice['status'])) {
                if ($invoice['status'] != 'Success') {
                    return response()->json(['status' => false, 'message' => 'Gagal Membuat Invoice']);
                }
                $va = null;
                $pay_url =  $detail_order['data']['checkout_url'] ?? $invoice['data']['pay_url'];
                $qr = null;
                if (isset($invoice['data']['nomor_va'])) {
                    $va .= $invoice['data']['nomor_va'];
                } else if (isset($invoice['data']['qr_link'])) {
                    $qr .= $invoice['data']['qr_link'];
                }
                $detail = [
                    'qr_link' => $qr,
                    'virtual_account' => $va,
                    'url' => $pay_url,
                    'payment_code' => $va,
                    'instructions' => $payment->instructions ?? $invoice['data']['panduan_pembayaran'],
                ];

                History::create([
                    'name' => $request->name,
                    'invoice_id' => $invoice_id,
                    'product' => ['game' => $game->name, 'ppob' => $denom->product->ppob, 'product_id' => $denom->product->id, 'name_provider' => $denom->product->name, 'denom_id' => $denom->id, 'denom' => $denom->name],
                    'payment' => array_merge($dataPayment, ['detail' => $detail]),
                    'price' => $finalPrice,
                    'other_prices' => ['discount' => $discount, 'tax' => $total_fee],
                    'whatsapp' => $request->whatsapp,
                    'expired_at' => Carbon::now()->addMinutes((int) $order['exp_order']),
                    'status' => 'pending',
                ]);
            }
            return response()->json(['status' => true, 'message' => 'Berhasil membuat order', 'data' => $invoice_id]);
        } elseif ($payment->provider == 'private') {
            History::create([
                'name' => $request->name,
                'invoice_id' => $invoice_id,
                'product' => ['game' => $game->name, 'ppob' => $denom->product->ppob,  'product_id' => $denom->product->id, 'name_provider' => $denom->product->name, 'denom_id' => $denom->id, 'denom' => $denom->name],
                'payment' => array_merge($dataPayment, ['detail' => null]),
                'price' => $finalPrice,
                'other_prices' => ['discount' => $discount, 'tax' => $total_fee],
                'whatsapp' => $request->whatsapp,
                'expired_at' => Carbon::now()->addMinutes((int) $order['exp_order']),
                'status' => 'pending',
            ]);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal membuat order']);
        }
    }

    public function submit_feedback(Request $request)
    {
        try {
            $history = History::where('invoice_id', $request->invoice_id)->first();
            if (!$history) {
                return response()->json(['status' => false, 'message' => 'Invoice tidak ditemukan']);
            }
            $arrayRating = ['1', '2', '3', '4', '5'];
            if (!in_array($request->star, $arrayRating)) {
                return response()->json(['status' => false, 'message' => 'Rating tidak valid']);
            }
            $request->message = strlen($request->message) > 200 ? substr($request->message, 0, 200) : $request->message;
            $data = ['invoice_id' => $history->invoice_id, 'product' => $history->product];
            Feedback::create([
                'name' => $history->name,
                'message' => $request->message,
                'rating' => $request->star,
                'information' => $data
            ]);
            return response()->json(['status' => true, 'message' => 'Berhasil mengirim feedback']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => 'Gagal mengirim feedback']);
        }
    }
    public function check_status(Request $request)
    {
        $history = History::where('invoice_id', $request->invoice_id)->first();
        if (!$history) {
            return response()->json(['status' => false, 'message' => 'Invoice tidak ditemukan']);
        }
        return response()->json(['status' => true, 'message' => 'Invoice ditemukan']);
    }

    public function load_more_feedback(Request $request)
    {
        $feedback = Feedback::orderBy('id', 'desc')->skip($request->count)->take(15)->select('id', 'message', 'reply_admin', 'rating', 'information', 'created_at')->get()->makeHidden(['created_at', 'updated_at']);
        return response()->json(['status' => true, 'message' => 'Berhasil mengambil data', 'feedback' => $feedback]);
    }
    public function reply_admin(Request $request)
    {
        if (!Auth::check()) return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        $feedback = Feedback::find($request->feedback_id);
        if (!$feedback) {
            return response()->json(['status' => false, 'message' => 'Feedback not found'], 403);
        }
        if (Auth::user()->role != 'admin') {
            return response()->json(['status' => false, 'message' => 'User not forbidden'], 403);
        }
        $feedback->reply_admin = $request->reply;
        $feedback->save();
        return response()->json(['status' => true, 'message' => 'Success send feedback']);
    }
    public function reset_license(Request $request)
    {
        $product = Product::where('id', $request->product_id)->first();
        $provider = Provider::find($product->provider_id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found']);
        }
        if ($provider->reset_license == 'disabled') {
            return response()->json(['status' => false, 'message' => 'Reset license not allowed']);
        }
        if ($provider->type_api == '1') {
            $data = ['api_key' => $provider->api_key, 'license' => $request->license, 'url' => $provider->url['reset']];
            $v1 = new Apiv1();
            $reset = $v1->reset($data);
            if (isset($reset['status']) && $reset['status'] == true) {
                return response()->json(['status' => true, 'message' => 'Reset license success']);
            } else {
                return response()->json(['status' => false, 'message' => 'Reset license failed']);
            }
        } elseif ($provider->type_api == '2') {
            $data = ['api_key' => $provider->api_key, 'license' => $request->license, 'url' => $provider->url['reset']];
            $v2 = new Apiv2();
            $reset = $v2->resetlicense($data);
            if (isset($reset['success']) && $reset['success'] == true) {
                return response()->json(['status' => true, 'message' => 'Reset license success']);
            } else {
                return response()->json(['status' => false, 'message' => 'Reset license failed']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Provider not allowed']);
        }
    }
}
