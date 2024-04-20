<?php

namespace EvanPiAlert\Util;

use PDOException;

/**
 * Singleton для загрузки настроек
 */
class Settings {

    const COMPANY_NAME = 'COMPANY NAME';

    const SYSTEMS_NAMES = 'SYSTEMS NAMES';

    const SYSTEMS_SETTINGS = 'SYSTEMS SETTINGS';

    const SYSTEMS_NETWORK_CHECK = 'SYSTEMS NETWORK CHECK';

    const AVERAGE_ALERT_INTERVAL_RATIO = 'AVERAGE ALERT INTERVAL RATIO';

    const CBMA_SERVICE_PASSWORD = 'CBMA SERVICE PASSWORD';

    const MESSAGE_STAT_SERVICE_PASSWORD = 'MESSAGE STATISTIC SERVICE PASSWORD';

    const MESSAGE_STAT_SERVICE_USER = 'MESSAGE STATISTIC SERVICE USER';

    const DATABASE_VERSION = 'DATABASE VERSION';

    const JOB_CACHE_REFRESH_TIME = 'JOB CACHE REFRESH TIME';

    const JOB_MESSAGE_STAT_REFRESH_TIME = 'JOB MESSAGE STATISTIC REFRESH TIME'; //время обновления задания статистики

    const MESSAGE_STAT_STORE_DAYS = 'MESSAGE STATISTIC STORE DAYS'; //время хранения в днях статистики

    const JOB_MESSAGE_STAT_DELETE_REFRESH_TIME = 'JOB MESSAGE STATISTIC DELETE REFRESH TIME'; //время обновления задания очистки статистики

    const ALERT_MESSAGE_COUNT = 'ALERT MESSAGE COUNT'; //пороговое значение для алерта по количеству сообщений

    const ALERT_PROC_TIME = 'ALERT MESSAGE PROCESSING TIME'; //пороговое значение для алерта по времени обработки

    const JOB_MESSAGE_STAT_ALERT_REFRESH_TIME = 'JOB MESSAGE STATISTIC ALERT REFRESH TIME'; //время обновления задания очистки статистики

    private static ?Settings $_instance = null;
    private array $cache = array();

    private function __construct () {}

    /**
     * Группировка параметров в веб интерфейсе
     * @return array settingGroupCode => название
     * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
     */
    public static function getSettingGroups() : array {
        return array(
            'COMPANY' => Text::settingsGroupCompany(),
            'SYSTEMS' => Text::settingsGroupSystem(),
            'OTHER' => Text::settingsGroupOther()
        );
    }

    protected static function instance(): Settings {
        if (static::$_instance === null) {
            static::$_instance = new Settings();
        }
        return static::$_instance;
    }

    /**
     * Установить в параметр значение
     * @param string $name
     * @param string $value
     * @return bool
     */
    public static function set(string $name, string $value): bool {
        $query = DB::prepare("UPDATE settings SET value = ? WHERE code = ?");
        return $query->execute(array($value, $name));
    }

    /**
     * Установить в параметр значение NOW()
     * @param string $name
     * @return bool
     */
    public static function setNow(string $name): bool {
        $query = DB::prepare("UPDATE settings SET value = NOW() WHERE code = ?");
        return $query->execute(array($name));
    }

    /**
     * Получить параметр для компании, для которой был инициализирован класс
     * @param string $name Название параметра
     * @return false|string Значение параметра, либо false, если такого параметра не существует
     */
    public static function get(string $name): bool|string {
        if ( isset(static::instance()->cache[$name]) ) {
            return static::instance()->cache[$name];
        }
        try {
            $query = DB::prepare("SELECT * FROM settings WHERE code = ?");
            $query->execute(array($name));
            if ($row = $query->fetch()) {
                static::instance()->cache[$name] = $row['value'];
                return (string)$row['value'];
            }
        } catch (PDOException) {
            return false;
        }
        return false;
    }
}
