<?php

namespace App\Libraries;

use App\Models\ConfigProvider;
use App\Models\DataProvider;
use Exception;
use Illuminate\Support\Str;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;

use DOMDocument;

class Apiv3
{
    public function cyrax($data)
    {
        $url = $data['url'] . '/' . $data['api_key'] . '/' . $data['durasi'];
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return $data;
    }
}
