<?php

namespace EvanPiAlert\Util;

class GeoIP {

    /** @noinspection HttpUrlsUsage */
    public static function getUserCountry() : string {
        $userIP = $_SERVER['REMOTE_ADDR'];
        $ch = curl_init('http://ip-api.com/json/'.$userIP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $result = curl_exec($ch);
        curl_close($ch);
        if ( $result === false ) {
            return 'en';
        }
        $result = json_decode($result);
        return $result->countryCode??'en';
    }

}