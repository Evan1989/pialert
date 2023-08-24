<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\essence\MessageStatistic;

class JobDeleteMessageStatistic extends JobAbstract {

    const JOB_INTERVAL = 86400; // Раз в сутки

    protected function executeJobInternal(): void {

        $store_days = Settings::get(Settings::MESSAGE_STAT_STORE_DAYS);
		if( $store_days !== false ) {
            MessageStatistic::DeleteFromDatabase( (int) $store_days );
        }
    }

    protected function getSettingName(): string {
        return Settings::JOB_MESSAGE_STAT_DELETE_REFRESH_TIME;
    }
}