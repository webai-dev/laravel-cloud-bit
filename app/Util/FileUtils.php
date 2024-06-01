<?php

namespace App\Util;
use Log;

class FileUtils{

    const SIZES  = ['B','KB','MB','GB','PB'];
    const RATIO  = 1000;

    const MIME_GROUPS = [
        'image'       => '/^image/',
        'video'       => '/^video/',
        'audio'       => '/^audio/',
        'pdf'         => '/^application\/pdf$/',
        'spreadsheet' => '/^application\/.*(excel|spreadsheet).*/',
        'text'        => '/^(text*|application\/msword|application\/rtf|application\/vnd.ms-excel|application\/vnd.ms-powerpoint|application\/vnd.oasis.opendocument.text|application\/vnd.oasis.opendocument.spreadsheet|application\/vnd.openxmlformats-officedocument*)/',

    ];

    public static function getHumanSize($bits,$unit = null){
        $number = $bits / 8;

        foreach (self::SIZES as $size) {
            if ($unit != null) {
                if ($size == $unit) {
                    return "$number$size";
                }
            }else{
                //Guess best display based on ratio
                if ($number < self::RATIO) {
                    return "$number$size";
                }
            }
            $number = $number / self::RATIO;
        }

        $last_size = self::SIZES[count(self::SIZES) - 1];
        return "$number$last_size";
    }

    public static function getMimeGroup($mime){
        foreach(self::MIME_GROUPS as $group => $regex){

            if (preg_match($regex,$mime)) {
                return $group;
            }
        }
        return "other";
    }
}