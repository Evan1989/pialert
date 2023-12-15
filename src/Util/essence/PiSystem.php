<?php

namespace EvanPiAlert\Util\essence;

class PiSystem {
    protected string $piSystemName; // equal value from alert
    protected string $piHost;
    protected bool $piStatisticEnable;
    protected string $piSID;

    public function __construct(string $piSystemName) {
        $this->piSystemName = $piSystemName;
    }

    public function setHost($piHost) : void {
        $this->piHost = $piHost;
    }
    public function setStatisticEnable( bool $piStatisticEnable ) : void {
        $this->piStatisticEnable = $piStatisticEnable;
    }

    public function getHost() : string {
        return $this->piHost;
    }
    public function getStatisticEnable() : bool {
        return $this->piStatisticEnable;
    }

    public function setSID($piSID) : void {
        $this->piSID = $piSID;
    }

    public function getSystemName() : string {
        return $this->piSystemName;
    }

    public function getSID() : string {
        if( isset($this->piSID) ) {
            return $this->piSID;
        }
        $parts = explodeWithDefault('.', $this->piSystemName, 3, $this->piSystemName);
        return mb_strtoupper($parts[1]);
    }

}