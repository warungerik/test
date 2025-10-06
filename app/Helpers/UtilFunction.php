<?php



function format_price($price)
{
    return number_format($price, 0, ',', '.');
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
