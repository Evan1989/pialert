<?php


namespace EvanPiAlert\Util;

/**
 * Кэширование данных в БД бота
 */
class Cache {

    /**
     * Получить объект из кэша
     * @param string $name Название объекта
     * @param string $className Вернуть как массив указанных классов (иначе просто array)
     * @return mixed Объект или false, если в кэше пусто
     */
    public static function get(string $name, string $className = ''): mixed {
        $query = DB::prepare("SELECT * FROM cache 
            WHERE name = ? AND (expiry_time > NOW() OR expiry_time is NULL)");
        $query->execute(array( $name ));
        if ($row = $query->fetch()) {
            $array = json_decode($row['content'], true);
            if ( empty( $className ) ) {
                return $array;
            }
            $result = array();
            foreach ($array as $element) {
                $result[] = Cache::castArrayToObject($element, $className);
            }
            return $result;
        }
        return false;
    }

    private static function castArrayToObject(array $array, string $className) {
        $object = new $className();
        foreach ($array as $field => $value) {
            $object->{$field} = $value;
        }
        return $object;
    }

    /**
     * Сохранить объект в кэше
     * @param string $name Название объекта
     * @param mixed $content объект
     * @param null|int $expiryTime Сколько секунд хранить. NULL - бессрочно
     * @return bool Успешно ли записался кэш
     */
    public static function save(string $name, mixed $content, ?int $expiryTime = 43200): bool {
        static::hardClear($name);
        if ( $expiryTime !== null ) {
            $expiryTime = date('Y-m-d H:i:s',  time()+$expiryTime);
        }
        $query = DB::prepare("INSERT INTO cache (name, content, expiry_time) VALUES (?, ?, ?)");
        return $query->execute(array(
            $name,
            json_encode($content),
            $expiryTime
        ));
    }

    /**
     * Удалить данные в кэше
     * @param bool|string $name Название объекта, если false, то удалит все, кроме бессрочных
     */
    public static function hardClear(bool|string $name = false) : void {
        if ( $name ) {
            $query = DB::prepare('DELETE FROM cache WHERE name = ?');
            $query->execute(array($name));
        } else {
            $query = DB::prepare("DELETE FROM cache WHERE expiry_time is NOT NULL");
            $query->execute(array());
        }
    }

    /**
     * Удалить старые объекты в кэше
     */
    public static function clearOld() : void {
        $query = DB::prepare("DELETE FROM cache WHERE expiry_time is NOT NULL AND expiry_time < NOW()");
        $query->execute(array());
    }

    /**
     * Узнать количество записей в кэше
     */
    public static function getCurrentSize() {
        $query = DB::prepare("SELECT count(*) as c FROM cache WHERE 1");
        $query->execute(array());
        if($row = $query->fetch()) {
            return $row['c'];
        }
        return 0;
    }
}
