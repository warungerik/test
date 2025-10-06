<?php

namespace App\Libraries;

use App\Models\Config;
use App\Models\ConfigWebsite;
use Exception;
use Illuminate\Support\Str;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;

use DOMDocument;

class Digiflazz
{
    public $username, $api_key;
    public function __construct()
    {

        $config = Config::first();
        $decode = $config->konfigurasi_ppob;
        $this->username = $decode['username'];
        $this->api_key = $decode['api_key'];
    }
    public function service()
    {
        $enc = $this->username . $this->api_key . 'pricelist';
        $encrypt = md5($enc);
        $api = 'https://api.digiflazz.com/v1/price-list';
        $data = [
            'cmd' => 'prepaid',
            'username' => $this->username,
            'sign' => $encrypt,
        ];
        $encode = json_encode($data);
        $headers = array(
            'Content-type: application/json',
            'Cache-control: no-cache',
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encode);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $res = curl_exec($ch);
        return json_decode($res, true);
    }
    public function balance()
    {
        $enc = $this->username . $this->api_key . 'depo';
        $encrypt = md5($enc);
        $api = 'https://api.digiflazz.com/v1/cek-saldo';
        $data = [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => $encrypt,
        ];
        $encode = json_encode($data);
        $headers = array(
            'Content-type: application/json',
            'Cache-control: no-cache',
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encode);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $res = curl_exec($ch);
        $decode = json_decode($res, true);
        return $decode;
    }
}
