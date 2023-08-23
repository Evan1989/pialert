<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\MessageStatAlertGen;
use EvanPiAlert\Util\MessageStatisticServiceCall;
use EvanPiAlert\Util\Settings;

class JobMessageStatisticAlert extends JobAbstract {

    const JOB_INTERVAL = 3600; // Раз в час

    protected function executeJobInternal(): void {
        $msgStatAlertUtil=new MessageStatAlertGen();
    }

    protected function getSettingName(): string {
        return Settings::JOB_MESSAGE_STAT_REFRESH_TIME;
    }
}