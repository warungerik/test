<?php



function format_price($price)
{
    return number_format($price, 0, ',', '.');
}
function calculateProfitData($row, $profitData, $round = false)
{
    $dataProfit = 0;
    $price = is_array($row) ? $row['price'] : $row->price ?? $row;
    if (is_array($profitData)) {
        foreach ($profitData as $key => $data) {
            if (($price >= $data['min'] && $price <= $data['max']) || ($key == '4' && $price >= $data['max'])) {
                $profit = calculateProfit($price, $data['profit'], $data['type']);
                $dataProfit = $round ? round($profit) : $profit;
                break;
            }
        }
    }

    return $dataProfit;
}
function calculateProfit($price, $profit, $type, $round = false)
{
    $price = (int) $price;
    switch ($type) {
        case 'percent':
            return $round ? round($price + ($price / 100) * $profit) : $price + ($price / 100) * $profit;
        case 'fixed':
            return $round ? round($price + $profit) : $price + $profit;
        case 'minus':
            return $round ? round($price - $profit) : $price - $profit;
        default:
            return $price;
    }
}


function random($length, $string = null)
{
    $string = $string != null ? $string : '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters =  $string;
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
