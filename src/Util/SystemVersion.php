<?php

namespace EvanPiAlert\Util;

/**
 * Класс для отслеживания версий системы и возможных обновлений
 */
class SystemVersion {

    /**
     * Версия кодовой базы
     * @return string MainVersion.MinorVersion.FixVersion
     */
    public static function getCodeVersion() : string {
        $composerData = file_get_contents(__DIR__.'/../../composer.json');
        return self::getVersionFromComposerJson($composerData)??"1.0.0";
    }

    /**
     * Версия кодовой базы
     * @return float MainVersion.MinorVersion
     */
    public static function getMainMinorCodeVersion() : float {
        list($mainVersion, $minorVersion, ) = explodeWithDefault('.', self::getCodeVersion(), 3, 0);
        return (float) ($mainVersion.'.'.$minorVersion);
    }

    /**
     * Версия базы данных, значение берется из БД. Меняется скриптом обновления базы из папки /src/install
     * @return string|false MainVersion.MinorVersion, false - если базы в системе нет
     */
    public static function getDatabaseVersion() : string|bool {
        return Settings::get(Settings::DATABASE_VERSION);
    }

    public static function isFinishInstallNeeded() : bool {
        return self::getMainMinorCodeVersion() > (float) self::getDatabaseVersion();
    }

    public static function isUpgradeNeeded() : bool {
        $githubVersion = self::getGithubVersion();
        if ( $githubVersion === false ) {
            return false;
        }
        return self::getCodeVersion() != $githubVersion;
    }

    public static function getGithubVersion() : string|false {
        $ch = curl_init(GITHUB_RAW_LINK.'/master/composer.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $result = curl_exec($ch);
        curl_close($ch);
        if ( $result === false ) {
            return false;
        }
        return self::getVersionFromComposerJson($result)??false;
    }

    protected static function getVersionFromComposerJson(string $composerData) : ?string {
        $composerData = json_decode($composerData);
        return $composerData->version??null;
    }
}