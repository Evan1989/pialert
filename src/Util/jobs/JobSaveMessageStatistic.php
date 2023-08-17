<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\MessageStatisticServiceCall;
use EvanPiAlert\Util\Settings;

class JobSaveMessageStatistic extends JobAbstract {

    const JOB_INTERVAL = 3600; // Раз в час

    protected function executeJobInternal(): void {
       $msgStat=new MessageStatisticServiceCall();
       $msgStat->serviceCall();
    }

    protected function getSettingName(): string {
        return Settings::JOB_MESSAGE_STAT_REFRESH_TIME;
    }
}