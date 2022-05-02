<?php

namespace EvanPiAlert\Util;

class GeoIP {

    /** @noinspection HttpUrlsUsage */
    public static function getUserCountry() : string {
        $userIP = $_SERVER['REMOTE_ADDR'];
        $ch = curl_init('http://ip-api.com/json/'.$userIP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($res, true);
        return $result['countryCode']??'en';
    }

}