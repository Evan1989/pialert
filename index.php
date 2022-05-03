<?php

// Если файла конфигурации нет, значит установка не завершена
if ( !is_file(__DIR__ . "/src/config.php") ) {
    Header("Location: /src/pages/install.php");
    exit();
}

use EvanPiAlert\Util\SystemVersion;
require_once(__DIR__."/src/autoload.php");
// Если версии не в порядке, значит мы в середине установки обновлений
if ( SystemVersion::isFinishInstallNeeded() ) {
    Header("Location: /src/pages/install.php");
    exit();
}

// Все в порядке, переходим на Рабочий стол
Header("Location: /src/pages/");
