<?php

namespace EvanPiAlert\Util;

use PDOException;

/**
 * Singleton для загрузки настроек
 */
class Settings {

    const COMPANY_NAME = 'COMPANY NAME';
    const LINK_TO_SUPPORT_RULES = 'LINK TO SUPPORT RULES';

    const SYSTEMS_NAMES = 'SYSTEMS NAMES';
    const SYSTEMS_NETWORK_CHECK = 'SYSTEMS NETWORK CHECK';

    const CBMA_SERVICE_PASSWORD = 'CBMA SERVICE PASSWORD';

    const DATABASE_VERSION = 'DATABASE VERSION';

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
