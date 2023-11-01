<?php

namespace EvanPiAlert\Util;

/**
 * Библиотека пользовательских текстов. Она учитывает язык пользователя.
 *
 * @method static string change()
 * @method static string pageGenerated()
 * @method static string surnameName()
 * @method static string avatar()
 * @method static string status()
 * @method static string actions()
 * @method static string addUser()
 * @method static string user()
 * @method static string employee()
 * @method static string responsibleEmployee()
 * @method static string save()
 * @method static string delete()
 * @method static string summary()
 * @method static string pieces()
 * @method static string perDay()
 * @method static string alertCount()
 * @method static string today()
 * @method static string name()
 * @method static string search()
 * @method static string date()
 * @method static string sender()
 * @method static string receiver()
 * @method static string object()
 * @method static string error()
 * @method static string comment()
 * @method static string requestList()
 * @method static string last()
 * @method static string reloadPage()
 * @method static string complete()
 * @method static string back()
 * @method static string or()
 * @method static string statusNew()
 * @method static string statusIgnore()
 * @method static string statusManual()
 * @method static string statusWait()
 * @method static string statusClose()
 * @method static string statusReopen()
 * @method static array dayNameArray()
 * @method static array yearsArray()
 * @method static array monthsArray()
 * @method static array daysArray()
 * @method static array hoursArray()
 * @method static array minutesArray()
 * @method static array secondsArray()
 * @method static string immediately()
 * @method static string menuGroupSettings()
 * @method static string menuGroupAnalytics()
 * @method static string menuDashboard()
 * @method static string menuAlerts()
 * @method static string menuRights()
 * @method static string menuUsers()
 * @method static string menuStatistics()
 * @method static string menuSettings()
 * @method static string menuReports()
 * @method static string menuRules()
 * @method static string menuProfile()
 * @method static string menuOnline()
 * @method static string menuMassAlerts()
 * @method static string menuSystems()
 * @method static string profilePageHeader()
 * @method static string profileLanguage()
 * @method static string profileAvatar()
 * @method static string profileChangePassword()
 * @method static string profileNewPassword()
 * @method static string profilePasswordChangeSuccess()
 * @method static string profilePasswordChangeFail()
 * @method static string profileTotalOnline()
 * @method static string usersPageHeader()
 * @method static string usersAddUserSuccess($arg1, $arg2)
 * @method static string usersLastActionTime($arg1)
 * @method static string settingsUpdateSuccess()
 * @method static string settingsUpdateFail()
 * @method static string settingsInstallButton()
 * @method static string settingsCommonSettings()
 * @method static string settingsHostname()
 * @method static string settingsCodeVersion()
 * @method static string settingsDataBaseVersion()
 * @method static string settingsGithubVersion()
 * @method static string settingsGroupCompany()
 * @method static string settingsGroupSystem()
 * @method static string settingsGroupOther()
 * @method static string statisticAllSystems()
 * @method static string statisticAlertGroupCount()
 * @method static string statisticAlert24HourCount()
 * @method static string statisticAlertTodayChart()
 * @method static string statisticAlertWeekCount()
 * @method static string statisticAlertMonthCount()
 * @method static string statisticAlertMonthChart()
 * @method static string statisticAlertTotalCount()
 * @method static string systemsCode()
 * @method static string systemsName()
 * @method static string systemsContact()
 * @method static string systemsComment()
 * @method static string chartsNormalForDay($arg1)
 * @method static string chartsAverageDay()
 * @method static string exportPageHeader()
 * @method static string exportLoadOnSystem()
 * @method static string exportMainAlertGroupBase()
 * @method static string exportLow()
 * @method static string exportErrorOnGenerateReport()
 * @method static string alertsPageHeader()
 * @method static string dashboardPageHeader()
 * @method static string dashboardCommentPlaceholder()
 * @method static string dashboardAlertLinkPlaceholder()
 * @method static string dashboardCheckAlertGroupAsCompleteButton()
 * @method static string dashboardCheckAlertGroupAsCompleteFail()
 * @method static string dashboardShowOnlyNewAlerts()
 * @method static string dashboardShowOnlyImportantAlerts()
 * @method static string dashboardNoConnectToPiSystem()
 * @method static string dashboardSupportOnline()
 * @method static string dashboardTitleToTopBillCounter()
 * @method static string dashboardRequisites()
 * @method static string dashboardMaskOrError()
 * @method static string dashboardMaskOrErrorTitle()
 * @method static string dashboardMaskAfterUnion()
 * @method static string dashboardFirstAlert()
 * @method static string dashboardLastAlert()
 * @method static string dashboardShowAlertButton()
 * @method static string dashboardShowStatisticButton()
 * @method static string dashboardShareLinkButton()
 * @method static string dashboardUnionGroupButton()
 * @method static string dashboardFindSameErrors()
 * @method static string dashboardUnionGroupButtonStep2()
 * @method static string dashboardThisGroup()
 * @method static string dashboardUnionSuccess()
 * @method static string dashboardUnionFail()
 * @method static string dashboardUnionFatalError()
 * @method static string dashboardLegend()
 * @method static string dashboardLegendActualAlert()
 * @method static string dashboardLegendNew()
 * @method static string dashboardLegendWait()
 * @method static string dashboardLegendManual()
 * @method static string dashboardLegendIgnore()
 * @method static string dashboardLegendClose()
 * @method static string dashboardLegendReopen()
 * @method static string dashboardLastUserId()
 * @method static string dashboardEditDate($date)
 * @method static string dashboardAvgBigCount()
 * @method static string apiCBMAServiceInfo($arg1)
 * @method static string apiNetworkCheckServiceInfo($arg1)
 * @method static string authorizationPageTitle()
 * @method static string authorizationPageHeader()
 * @method static string authorization403Error()
 * @method static string installPageHeader()
 * @method static string installNextStep()
 * @method static string installError($arg1)
 * @method static string installStep1Header()
 * @method static string installStep1Body()
 * @method static string installDatabaseUpdateError($arg1)
 * @method static string installStep2Header()
 * @method static string installStep2Body()
 * @method static string installStep2Success()
 * @method static string installUpdateHeader()
 * @method static string installUpdateBody($arg1)
 * @method static string installUpgradeBody($arg1, $arg2)
 * @method static string installAutoUpgradeBody()
 * @method static string installAutoUpgradeSuccess()
 * @method static string installAutoUpgradeFail($arg1)
 * @method static string installUpdateSuccess($arg)
 * @method static string installFinish()
 * @method static string installTrySystem()
 * @method static string onlinePageHeader()
 * @method static string onlineModeUsually()
 * @method static string onlineModeThisWeek()
 * @method static string massAlertsPageHeader()
 * @method static string massAlertsSearchBlockHeader()
 * @method static string massAlertsFoundedBlockHeader()
 * @method static string massAlertsFoundedCount()
 * @method static string massAlertsReplace()
 * @method static string massAlertsRemoveIcon()
 * @method static string genericExceptionTitle()
 * @method static string systemsPageHeader()
 * @method static string systemCard()
 * @method static string messageAlertCount(string $interface, string $averageCount, string $currentCount)
 * @method static string messageAlertProcTime(string $interface, string $averageProcessingTime, string $currentProcessingTime)
 **/
class Text {

    const RU = 'ru';
    const EN = 'en';

    const LANGUAGES = [self::EN, self::RU];

    const REPLACE_PATTERN = '%%%%';

    private static ?Text $instance = null;

    public static function language( ?string $language = null ) : string {
        if ( is_null($language) ) {
            static::instance()->defaultInitialization();
        } else {
            static::instance()->setLanguage($language);
        }
        return static::instance()->language;
    }

    public static function instance(): Text {
        if (static::$instance === null) {
            static::$instance = new Text();
        }
        return static::$instance;
    }

    public static function __callStatic($name, $args) {
        return static::instance()->getText($name, $args);
    }

    private string $language;
    private array $texts;

    private function __construct () {}

    private function setLanguage( ?string $language ): bool {
        if ( isset($this->language) && $language == $this->language ) {
            return true;
        }
        if ( !in_array($language, static::LANGUAGES)) {
            $language = static::RU;
        }
        $settingFile = __DIR__."/../localization/".$language.".php";
        if ( is_file($settingFile) ) {
            include($settingFile);
        }
        if (isset($texts)) { // $texts берется из файла локализации
            $this->texts = $texts;
            $this->language = $language;
            $_SESSION['language'] = $language;
            return true;
        }
        return false;
    }

    private function getText( string $name, $args = array() ) {
        $this->checkInit();
        $text = $this->texts[$name] ?? "";
        if ( empty($text) ) {
            $text = @$this->texts[str_replace('Array', '', $name)];
        }
        if ( empty($text) ) {
            return $name;
        }
        if ( !empty($args) ) {
            foreach ($args as $value) {
                $text = preg_replace('#'.static::REPLACE_PATTERN.'#u', $value, $text, 1);
            }
        }
        if ( is_array($text) && mb_strpos($name, 'Array') === false ) {
            return $text[array_rand($text)];
        }
        return $text;
    }

    private function defaultInitialization(): bool {
        if ( empty($this->texts) ) {
            $language = $_SESSION['language']??GeoIP::getUserCountry();
            return $this->setLanguage($language);
        }
        return true;
    }

    private function checkInit(): void {
        if ( empty($this->texts) ) {
            if ( $this->defaultInitialization() ) {
                return;
            }
            $error = "Пользовательские тексты не инициализированы.";
            error_log($error);
            die($error);
        }
    }

}