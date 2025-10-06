<?php

namespace App\Libraries;

use App\Models\Config;
use App\Models\ConfigWebsite;
use Exception;
use Illuminate\Support\Str;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;

use DOMDocument;

class Tokopay
{
    public $merchantId, $secretKey;
    public $apiUrl = "https://api.tokopay.id";

    public function __construct()
    {
        $config = Config::first();
        $decode = $config->konfigurasi_payment['tokopay'];
        $this->merchantId = $decode['merchant'];
        $this->secretKey = $decode['secret'];
    }

    public function generateSignature($refId)
    {
        $formula = $this->merchantId . ":" . $this->secretKey . ":" . $refId;
        $signatureTrx = md5($formula);
        return $signatureTrx;
    }

    public function createOrder($nominal, $ref_id, $kodeChannel)
    {
        $mid = $this->merchantId;
        $secret = $this->secretKey;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl . "/v1/order?merchant=$mid&secret=$secret&ref_id=$ref_id&nominal=$nominal&metode=$kodeChannel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function createAdvanceOrder($data = []): array
    {
        $data['merchant_id'] = $this->merchantId;
        $data['signature'] = $this->generateSignature($data['reff_id']);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl . '/v1/order',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
}
