<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiSystem;

class ManagePiSystem{

    protected ?array $piSystems = null;

    public function __construct() {
        $systemInfo = json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true);
        $piSystem_array = array();
        foreach($systemInfo as $key => $value) {
            $piSystem = new PiSystem($key);
            $piSystem->setHost($value['host']);
            $piSystem->setStatisticEnable( $value['statEnable'] );
            $piSystem->setSID($value['SID']);
            $piSystem_array[] = $piSystem;
        }
        $this->piSystems = $piSystem_array;
    }

    public function deletePiSystem($systemName):bool{
        $systemInfo = json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true);
        unset($systemInfo[$systemName]);
        return Settings::set(Settings::SYSTEMS_SETTINGS,json_encode($systemInfo));
    }

    /**
     * @return PiSystem[]
     */
    public function getPiSystems() : array {
        return $this->piSystems;
    }

}