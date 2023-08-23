<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\Settings;

abstract class JobAbstract {

    const JOB_INTERVAL = 600; // раз в 10 минут

    /**
     * Запустить фоновое задание.
     * Внутри учитывается необходимая частота вызова задания
     * @return void
     */
    public function executeJob() : void {
        if ( !$this->isNeedToStartJob($this->getSettingName(), static::JOB_INTERVAL) ) {
            return;
        }
        Settings::setNow($this->getSettingName());
        $this->executeJobInternal();
    }

    abstract protected function getSettingName() : string;
    abstract protected function executeJobInternal() : void;

    protected function isNeedToStartJob(string $settingName, int $secondsInterval) : bool {
        $lastExecuteTime = strtotime(Settings::get($settingName));
        return $lastExecuteTime === false || time() - $lastExecuteTime > $secondsInterval;
    }
}