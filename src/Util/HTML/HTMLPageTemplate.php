<?php

namespace EvanPiAlert\Util\HTML;

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\SystemVersion;
use EvanPiAlert\Util\Text;

class HTMLPageTemplate {

    protected ?AuthorizationAdmin $authorizationAdmin;

    public function __construct(?AuthorizationAdmin $authorizationAdmin = null) {
        $this->authorizationAdmin = $authorizationAdmin;
    }

    protected function calculateUserLanguage() : void {
        if ( !isset($_GET['language']) ) {
            return;
        }
        $language = mb_substr($_GET['language'],0, 2);
        Text::language($language);
        Header("Location: ".str_replace("language=".$language, "", $_SERVER['REQUEST_URI']));
        exit();
    }

    protected function calculateAjaxSystemInfo() : void {
        if (!isset($_POST['ajaxSystemAbout'])) {
            return;
        }
        $addLine = '';
        $githubVersion = SystemVersion::getGithubVersion();
        if ( $githubVersion !== false ) {
            $addLine = Text::settingsGithubVersion().": ".$githubVersion;
            if ( $this->authorizationAdmin->checkAccessToMenu(6) &&
                (SystemVersion::isFinishInstallNeeded() || SystemVersion::isUpgradeNeeded() )
            ) {
                $addLine .= " <a href='install.php'>" . static::getIcon('cloud-download') . "</a>";
            }
            $addLine .= "<br>";
        }
        echo "<h5>PiAlert</h5>".
            Text::settingsCodeVersion().": ".SystemVersion::getCodeVersion()."<br>".
            $addLine.
            "Source code: <a href='".GITHUB_PROJECT_LINK."' target='_blank'>".GITHUB_PROJECT_LINK."</a>";
        exit();
    }

    protected function calculateAjaxOrTransitCalls() : void {
        $this->calculateUserLanguage();
        $this->calculateAjaxSystemInfo();
    }

    public function getPageHeader(string $title) : string {
        $this->calculateAjaxOrTransitCalls();
        return "<!doctype html>
<html lang='ru'>
    <head>
        <title>".$title. "</title>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
        <link rel='shortcut icon' href='/favicon.png?v2' id='favicon'>
        <link href='/src/css/bootstrap.min.css' rel='stylesheet' type='text/css'>
        <link href='/src/css/main.css?v35' rel='stylesheet' type='text/css'>
        <script src='/src/js/jquery-3.6.0.min.js'></script>
    </head>
    <body>".
        $this->getPagesMenu().
        "<div class='container-fluid'>
            <main role='main'>
                <div class='container-fluid'>";
    }

    public function getPageFooter(string $additionalJavaScript = '') :string {
        $text = "";
        if ( !is_null($this->authorizationAdmin) ) {
            $text .= "<div class='alert alert-info' role='alert'>
                        " . Text::pageGenerated() . " " . date("Y-m-d H:i:s") . "
                    </div>";
        }
        $text .= "  <div class='modal fade' id='modal_piAlertDefault' tabindex='-1' role='dialog' aria-hidden='true'>
                        <div class='modal-dialog modal-xl' role='document'>
                            <div class='modal-content'>
                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                <div class='modal-body overflow-auto'></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <script src='/src/js/jquery.tablesorter.min.js'></script>
        <script src='/src/js/popper.min.js'></script>
        <script src='/src/js/bootstrap.min.js'></script>
        <script src='/src/js/chart.min.js'></script>
        <script src='/src/js/base.js?v=66'></script>".
        $additionalJavaScript.
    "</body>
</html>";
        return $text;
    }

    public static function getIcon(string $iconName, int $size = 16): string {
        return "<img src='/vendor/twbs/bootstrap-icons/icons/".$iconName.".svg' width='".$size."' height='".$size."' alt='' class='icon d-lg-none d-xl-inline'>";
    }

    protected function getChooseLanguageMenu() : string {
        $text = "<nav class='navbar navbar-expand-lg navbar-dark fixed-top shadow'>
              <div class='container-fluid'>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse'  data-bs-target='#mainMenu' aria-controls='#mainMenu'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='mainMenu'>
                    <ul class='navbar-nav'>";
        foreach (Text::LANGUAGES as $language) {
            $text .= "  <li class='nav-item'>
                            <a href='".$_SERVER['REQUEST_URI']."?&language=".$language."' class='nav-link ".(Text::language()==$language?'active':'')."'>".mb_strtoupper($language)."</a>
                        </li>";
        }
        $text .= "	</ul>
                </div>
               </div>
              </nav>";
        return $text;
    }

    protected function getPagesMenu(): string {
        if ( is_null($this->authorizationAdmin) ) {
            return $this->getChooseLanguageMenu();
        }
        $text = "<nav class='navbar navbar-expand-lg navbar-dark fixed-top shadow'>
              <div class='container-fluid'>
                <div class='company-logo'><a href='javascript:loadSystemAbout()'>".Settings::get(Settings::COMPANY_NAME)." PiAlert</a></div>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse'  data-bs-target='#mainMenu' aria-controls='#mainMenu'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='mainMenu'>
                    <ul class='navbar-nav'>";
        $query = DB::prepare("
            SELECT *
            FROM user_rights as r LEFT JOIN pages p on r.menu_id = p.menu_id
            WHERE user_id = ? AND page_caption IS NOT NULL
            ORDER BY p.number");
        $query->execute(array( $this->authorizationAdmin->getUserId() ));
        $links = array();
        while ($row = $query->fetch()) {
            $group = $row['group_caption'];
            $caption = $row['page_caption'];
            if ( $group ) {
                $group_caption = $this->getIcon($row['group_icon'])." ".Text::$group();
                $links[$group_caption][] = array($row['url'], $this->getIcon($row['page_icon'])." ".Text::$caption() );
            } else {
                $links[] = array($row['url'], $this->getIcon($row['page_icon'])." ".Text::$caption() );
            }
        }
        foreach ($links as $groupCaption => $data) {
            if ( is_int($groupCaption) ) {
                $text .= $this->getMenuElement($data[0], $data[1]);
            } else {
                $text .= $this->getPagesSubMenu($groupCaption, $data);
            }
        }
        $linkToSupportRules = Settings::get(Settings::LINK_TO_SUPPORT_RULES);
        if ( $linkToSupportRules ) {
            $text .= "	<li class='nav-item'>
                            <a href='".$linkToSupportRules."' class='nav-link' target='_blank'>
                                ".$this->getIcon('file-earmark-richtext')." ".Text::menuRules()."
                            </a>
                        </li>";
        }
        $text .= "		<li class='nav-item'>
                            <a href='profile.php' class='nav-link'>
                                ".$this->authorizationAdmin->getUser()->getAvatarImg('menu-user-avatar', $this->getIcon('person'))." ".Text::menuProfile()."
                            </a>
                        </li>
                        <li class='nav-item'>
                            <a href='".$_SERVER['PHP_SELF']."?logout=1' class='nav-link'>".$this->getIcon('box-arrow-right')." Logout</a>
                        </li>
                    </ul>
                </div>
               </div>
              </nav>";
        return $text;
    }

    protected function getMenuElement( string $url, string $caption ): string {
        $active = (mb_strpos($url, $_SERVER['PHP_SELF']) !== false);
        return "<li class='nav-item'>
                    <a href='".$url."' class='nav-link ".($active?'active':'')."'>".$caption."</a>
                </li>";
    }

    protected function getPagesSubMenu($caption, $elements ): string {
        if ( count($elements) == 1 ) {
            return $this->getMenuElement($elements[0][0], $caption);
        }
        $subPages = array();
        foreach ($elements as $element) {
            $active = (mb_strpos($element[0], $_SERVER['PHP_SELF']) !== false);
            $subPages[] = "  <a href='".$element[0]."' class='dropdown-item ".($active?'active':'')."'>".$element[1]."</a>";
        }
        return "<li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle' data-bs-toggle='dropdown' href='#' role='button' aria-haspopup='true' aria-expanded='false'>".$caption."</a>
                    <div class='dropdown-menu'>".implode('', $subPages)."</div>
                </li>";
    }
}