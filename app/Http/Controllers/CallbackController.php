<?php

namespace App\Http\Controllers;

use App\Libraries\Apiv1;
use App\Libraries\Apiv2;
use App\Libraries\Apiv3;
use App\Models\Denom;
use App\Models\History;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Stock;
use Illuminate\Http\Request;

class CallbackController extends Controller
{
    public function callback_tokopay(Request $request)
    {
        if ($request->isMethod('get')) {
            return abort(404);
        }
        $config = config('website.all');
        $provider = $config->konfigurasi_payment['tokopay'];
        $json = $request->getContent();
        $data = json_decode($json, true);
        $history = History::where('invoice_id', $data['reff_id'])->first();
        $status = $data['status'];
        if ($status === "Success" || $status === "Completed") {
            $invoice_id = $data['reff_id'];
            $sign = md5($provider['merchant'] . ':' . $provider['secret'] . ':' . $invoice_id);
            if ($sign !== $data['signature']) {
                return response()->json(['status' => false], 403);
            }
            if ($data['data']['merchant_id'] != $provider['merchant']) {
                return response()->json(['status' => false, 'message' => 'Merchant ID tidak valid'], 403);
            }
            if ($history) {
                $sellernotes = $history->seller_notes;
                $product = Product::with('game')->where('id', $history->product['product_id'])->first();
                if (!$product) {
                    $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                    $history->status = 'failed';
                    $history->seller_notes = $sellernotes;
                    return;
                }
                $provider = Provider::where('id', $product->provider_id)->first();
                if (!$product) {
                    $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                    $history->status = 'failed';
                    $history->seller_notes = $sellernotes;
                    $history->save();
                    return;
                }
                $denom = Denom::where('id', $history->product['denom_id'])->first();
                if (!$denom) {
                    $history->status = 'failed';
                    $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                    $history->seller_notes = $sellernotes;
                    $history->save();
                    return;
                }
                if ($provider->type_api == '0') {
                    $stock = Stock::where('product_id', $product->id)->where('denom_id', $denom->id)->first();
                    if ($stock) {
                        $sellernotes['notes'] = 'License : ' . $stock->license;
                        $sellernotes['download'] = json_decode($product->download, true);
                        $history->status = 'success';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        $stock->delete();
                        return response()->json(['status' => true, 'message' => 'Berhasil']);
                    } else {
                        $history->status = 'failed';
                        $sellernotes['notes'] = 'Stock license tidak tersedia, silahkan menghubungi admin';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => false, 'message' => 'Stock license tidak tersedia, silahkan menghubungi admin']);
                    }
                } else if ($provider->type_api == '1') {
                    $data = ['name' => $history->name, 'api_key' => $provider->api_key, 'durasi' => $denom['duration'], 'code_game' => $product->game->code_game, 'url' => $provider->url['register']];
                    $v1 = new Apiv1();
                    $order = $v1->order($data);
                    if (isset($order['status'])) {
                        if ($order['status'] == false) {
                            $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                            $history->status = 'failed';
                            $history->sellernotes = $sellernotes;
                            $history->save();
                            return response()->json(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
                        }
                        $sellernotes['notes'] = 'License : ' . $order['data']['license'];
                        $sellernotes['download'] = json_decode($product->download, true);
                        $history->status = 'success';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => true, 'message' => 'Berhasil']);
                    } else {
                        $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                        $history->status = 'failed';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
                    }
                } else if ($provider->type_api == '2') {
                    $data = ['name' => $history->name, 'api_key' => $provider->api_key, 'game_id' => $product->game_id, 'durasi' => $denom->duration, 'url' => $provider->url];
                    $v2 = new Apiv2();
                    $order = $v2->register($data);
                    if (isset($order['success'])) {
                        if ($order['success'] == false) {
                            $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                            $history->status = 'failed';
                            $history->sellernotes = $sellernotes;
                            $history->save();
                            return response()->json(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
                        }
                        $sellernotes['notes'] = 'License : ' . $order['data']['Member Key'];
                        $sellernotes['download'] = json_decode($product->download, true);
                        $history->status = 'success';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => true, 'message' => 'Berhasil']);
                    } else {
                        $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                        $history->status = 'failed';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
                    }
                } elseif ($provider->type_api == '3') {
                    $data = ['url' => $provider->url['register'], 'api_key' => $provider->api_key, 'durasi' => $denom->duration];
                    $v3 = new Apiv3();
                    $order = $v3->cyrax($data);
                    if (isset($order['success'])) {
                        if ($order['success'] == false) {
                            $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                            $history->status = 'failed';
                            $history->seller_notes = $sellernotes;
                            $history->save();
                            return response()->json(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
                        }
                        $sellernotes['notes'] = 'License : ' . $order['keycode'];
                        $sellernotes['download'] = json_decode($product->download, true);
                        $history->status = 'success';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => true, 'message' => 'Berhasil']);
                    } else {
                        $sellernotes['notes'] = 'Gagal mengambil data, silahkan hubungi admin';
                        $history->status = 'failed';
                        $history->seller_notes = $sellernotes;
                        $history->save();
                        return response()->json(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
                    }
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Invoice ID tidak valid']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Status tidak valid']);
        }
    }
}
