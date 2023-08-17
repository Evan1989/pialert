<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\Settings;

class PiSystem {
    protected string $piSystemName; // from alert

    public function __construct(string $piSystemName) {
        $this->piSystemName = $piSystemName;
    }

    public function getSID() : string {
        $systemNames = Settings::get(Settings::SYSTEMS_SETTINGS);
        if ( $systemNames !== false ) {
            $systemNames = json_decode($systemNames, true);
            if ( isset($systemNames[$this->piSystemName]) ) {
                return $systemNames[$this->piSystemName]['SID'];
            }
        }
        $parts = explodeWithDefault('.', $this->piSystemName, 3, $this->piSystemName);
        return mb_strtoupper($parts[1]);
    }



}