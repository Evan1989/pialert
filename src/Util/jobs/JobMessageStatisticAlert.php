<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\MessageStatAlertGenerator;
use EvanPiAlert\Util\Settings;

class JobMessageStatisticAlert extends JobAbstract {

    const JOB_INTERVAL = 3600; // Раз в час

    protected function executeJobInternal(): void {
        $msgStatAlertUtil = new MessageStatAlertGenerator();
        $msgStatAlertUtil->generatePiAlertMessageCount();
        $msgStatAlertUtil->generatePiAlertMessageProcTime();
    }

    protected function getSettingName(): string {
        return Settings::JOB_MESSAGE_STAT_ALERT_REFRESH_TIME;
    }
}