<?php

namespace EvanPiAlert\Util;

require_once(__DIR__."/../config.php");

use PDO;
use PDOStatement;
use PDOException;

set_time_limit(1000);

/**
 * Singleton для работы с БД
 * @method static PDOStatement prepare(string $query)
 * @method static int|false exec(string $query)
 * @method static string lastInsertId()
 */
class DB {

	protected static ?PDO $_instance = null;

	public static function instance(): PDO {
		if (static::$_instance === null) {
			try {
                static::$_instance = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_LOGIN, DB_PASSWORD);
			}
			catch(PDOException $e) {
				die( $e->getMessage() );
			}
            static::$_instance->exec("SET NAMES utf8mb4");
		}
		return static::$_instance;
	}

	public static function __callStatic($method, $args) {
		return call_user_func_array(array(static::instance(), $method), $args);
	}

}
