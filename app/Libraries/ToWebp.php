<?php

namespace App\Libraries;

use App\Models\ConfigWebsite;
use Exception;
use Illuminate\Support\Str;

use DOMDocument;

class ToWebp
{

    public function __construct() {}

    public function convert($fullPath, $outPutQuality = 100, $deleteOriginal = false)
    {

        $fullPath = $fullPath;
        $outPutQuality  = $outPutQuality;
        $deleteOriginal = $deleteOriginal;

        if (file_exists($fullPath)):

            $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
            $extension = $ext;
            $newFilefullPath = str_replace('.' . $ext, '.webp', $fullPath);


            $isValidFormat = false;

            if ($extension == 'png' || $extension == 'PNG') {
                $img = imagecreatefrompng($fullPath);
                $isValidFormat = true;
            } else if ($extension == 'jpg' || $extension == 'JPG' || $extension == 'JPEG' || $extension == 'jpeg') {
                $img = imagecreatefromjpeg($fullPath);
                $isValidFormat = true;
            } else if ($extension == 'gif' || $extension == 'GIF') {
                $img = imagecreatefromgif($fullPath);
                $isValidFormat = true;
            }

            if ($isValidFormat) {

                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                imagewebp($img, $newFilefullPath, $outPutQuality);
                imagedestroy($img);

                if ($deleteOriginal) {
                    unlink($fullPath);
                }
            } else {
                return (object) array('error' => 'Given file cannot be converted to webp', 'status' => 0);
            }

            $newPathInfo = explode('/', $newFilefullPath);
            $finalImage  = $newPathInfo[count($newPathInfo) - 1];

            $result = array(
                "fullPath" => $newFilefullPath,
                "file" => $finalImage,
                "status" => 1
            );

            return (object) $result;

        else:
            return (object) array('error' => 'File does not exist', 'status' => 0);
        endif;
    }
}
