<?php

namespace App\Libraries;

use App\Models\ConfigProvider;
use App\Models\DataProvider;
use Exception;
use Illuminate\Support\Str;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;

use DOMDocument;

class Apiv2
{
    public function register($data)
    {
        $durasi_id = $this->paket_id($data['api_key'], $data['game_id'], $data['durasi'], $data);
        if ($durasi_id != false) {
            try {
                $api_key = $data['api_key'];
                $data['paket_id'] = $durasi_id;
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $data['url']['register'] . '?api_key=' . $api_key,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $data,
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                $decode = json_decode($response, true);
                return $decode;
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }
    function paket_id($api, $game_id, $durasi, $url)
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url['url']['paket'] . '?api_key=' . $api . '&game_id=' . $game_id,
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
            $decode = json_decode($response);
            if ($decode->success == true) {
                $loop = $decode->data;
                $paket_id = '';
                foreach ($loop as $row) {
                    if ($row->durasi + $row->bonus_durasi == $durasi) {
                        $paket_id .= $row->id;
                        break;
                    } else {
                        $paket_id .= false;
                    }
                }
                return $paket_id;
            }
        } catch (Exception $e) {
            return false;
        }
    }
    function game_id($api, $url)
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL =>  $url['url']['game'] . '?api_key=' . $api,
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
        } catch (\Throwable $e) {
            return false;
        }
    }
    function resetlicense($data)
    {

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $data['url'] . '?api_key=' . $data['api_key'] . '&member_key=' . $data['license'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response, true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
