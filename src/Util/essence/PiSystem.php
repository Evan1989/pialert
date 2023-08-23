<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\Settings;

class PiSystem {
    protected string $piSystemName; // from alert
    protected string $piHost;
    protected bool $piStatisticEnable;
    protected string $piSID;

    public function __construct(string $piSystemName) {
        $this->piSystemName = $piSystemName;
    }

    public function setHost($piHost){
        $this->piHost=$piHost;
    }
    public function setStatisticEnable(bool $piStatisticEnable){
        $this->piStatisticEnable=$piStatisticEnable;
    }

    public function getHost():string{
        return $this->piHost;
    }
    public function getStatisticEnable():bool{
        return $this->piStatisticEnable;
    }

    public function setSID($piSID){
        $this->piSID=$piSID;
    }

    public function getSystemName():string{
        return $this->piSystemName;
    }

    public function getSID() : string {
       /* $systemNames = Settings::get(Settings::SYSTEMS_NAMES);
        if ( $systemNames !== false ) {
            $systemNames = json_decode($systemNames, true);
            if ( isset($systemNames[$this->piSystemName]) ) {
                return $systemNames[$this->piSystemName];
            }
        }*/
        if(isset($this->piSID))
        {
            return $this->piSID;
        }
        $parts = explodeWithDefault('.', $this->piSystemName, 3, $this->piSystemName);
        return mb_strtoupper($parts[1]);
    }

}