<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiSystem;

class ManagePiSystem {

    static protected ?array $piSystems;

    public static function internalInit() : void {
        if ( isset(ManagePiSystem::$piSystems) ) {
            return;
        }
        $systemInfo = json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true);
        $piSystem_array = array();
        foreach ($systemInfo as $key => $value) {
            $piSystem = new PiSystem($key);
            $piSystem->setHost( $value['host']??'' );
            $piSystem->setStatisticEnable( $value['statEnable']??false );
            $piSystem->setSID( $value['SID']??'' );
            $piSystem_array[ $key ] = $piSystem;
        }
        ManagePiSystem::$piSystems = $piSystem_array;
    }

    public static function deletePiSystem($systemName) : bool{
        $systemInfo = json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true);
        unset($systemInfo[$systemName]);
        unset(static::$piSystems[$systemName]);
        return Settings::set(Settings::SYSTEMS_SETTINGS,json_encode($systemInfo));
    }

    /**
     * @return PiSystem[]
     */
    public static function getPiSystems() : array {
        static::internalInit();
        return static::$piSystems;
    }

}