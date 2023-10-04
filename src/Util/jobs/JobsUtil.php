<?php

namespace EvanPiAlert\Util\jobs;

/**
 * Класс для управления фоновыми заданиями, нужными для работы системы
 */
class JobsUtil {

    /**
     * Запустить необходимые фоновые задания.
     * Метод можно вызывать нужное количество раз как из кода, так и из cron на уровне ОС
     * Внутри учитывается необходимая частота вызова самих заданий
     * @return void
     */
    public static function executeNeededJobs() : void {
        $job = new JobCacheRefresh();
        $job->executeJob();

        $job_msg_stat = new JobSaveMessageStatistic();
        $job_msg_stat->executeJob();

        $job_msg_stat_delete = new JobDeleteMessageStatistic();
        $job_msg_stat_delete->executeJob();

        $job_msg_alert = new JobMessageStatisticAlert();
        $job_msg_alert->executeJob();

        // Сюда можно добавить новое фоновое задание, если надо
    }

}