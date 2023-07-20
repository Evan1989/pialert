<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\Cache;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\Settings;

class JobCacheRefresh extends JobAbstract {

    const JOB_INTERVAL = 86400; // Раз в сутки

    protected function executeJobInternal(): void {
        AuthorizationAdmin::deleteAllOldToken();
        Cache::clearOld();
        // Сделаем перерасчет поля errTextMainPart для алертов
        $query = DB::prepare("SELECT * FROM alert_group WHERE errTextMainPart is NULL");
        $query->execute(array());
        while ($row = $query->fetch()) {
            $alertGroup = new PiAlertGroup($row);
            $alertGroup->saveToDatabase();
        }
    }

    protected function getSettingName(): string {
        return Settings::JOB_CACHE_REFRESH_TIME;
    }
}