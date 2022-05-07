<?php

use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\SelfUpdateCode;
use EvanPiAlert\Util\SystemVersion;
use EvanPiAlert\Util\Text;

require_once(__DIR__."/../autoload.php");

$page = new HTMLPageTemplate();
echo $page->getPageHeader(Text::installPageHeader());

if ( is_file(__DIR__ . "/../config.php") === false ) {
    echo "<div class='card mb-4 shadow'>
	        <div class='card-header'>".Text::installStep1Header()."</div>
            <div class='card-body overflow-auto'>
                ".Text::installStep1Body()."
                <br><br>
                <code><pre>".htmlspecialchars(file_get_contents(__DIR__.'/../install/config.php.sample'))."</pre></code>
                <a href='install.php' class='btn btn-primary'>".Text::complete()."</a>
            </div>
	    </div>".
	    $page->getPageFooter();
    exit();
}

function executeScriptInDataBase(int $mainVersion, int $minorVersion, bool $skipNotFoundVersions = false) : bool|string {
    $file = __DIR__.'/../install/database_'.$mainVersion.'_'.$minorVersion.'.sql';
    if ( is_file($file) === false ) {
        if ( $skipNotFoundVersions ) {
            return '';
        }
        return Text::installDatabaseUpdateError($mainVersion.'.'.$minorVersion);
    }
    try {
        DB::exec(file_get_contents($file));
    } catch(PDOException $e) {
        return $e->getMessage();
    }
    return true;
}

if ( SystemVersion::getDatabaseVersion() === false ) {
    echo "<div class='card mb-4 shadow'>
	        <div class='card-header'>".Text::installStep2Header()."</div>
            <div class='card-body overflow-auto'>";
    if ( isset($_GET['create'] ) ) {
        $result = executeScriptInDataBase(1,0);
        if ( $result === true ) {
            echo "<div class='alert alert-success' role='alert'>
                        ".Text::installStep2Success()."
                        <br><br>
                        <a href='install.php' class='btn btn-primary'>".Text::installNextStep()."</a>
                </div>";
        } else {
            echo "  <div class='alert alert-danger' role='alert'>".Text::installError($result)."</div>";
        }
    } else {
        echo Text::installStep2Body().
                "<br><br>
                <a href='install.php?create=1' class='btn btn-primary'>".Text::installNextStep()."</a>";
    }
    echo "   </div>
	    </div>";
} elseif ( SystemVersion::isFinishInstallNeeded() ) {
    echo "<div class='card mb-4 shadow'>
	        <div class='card-header'>".Text::installUpdateHeader()."</div>
            <div class='card-body overflow-auto'>";
    if ( isset($_GET['update'] ) ) {
        $targetVersion = 10*SystemVersion::getMainMinorCodeVersion();
        $curVersion = 10*SystemVersion::getDatabaseVersion();
        for ($curVersion+=1; $curVersion <= $targetVersion; $curVersion++) {
            $result = executeScriptInDataBase(floor($curVersion/10), $curVersion%10, $curVersion < $targetVersion);
            if ($result === true) {
                echo "<div class='alert alert-success' role='alert'>".Text::installUpdateSuccess($curVersion)."</div>";
            } elseif ( $result ) {
                echo "  <div class='alert alert-danger' role='alert'>".Text::installError($result)."</div>";
                break;
            }
        }
        echo "  <br><br>
                <a href='settings.php' class='btn btn-primary'>".Text::back()."</a>";
    } else {
        echo Text::installUpdateBody(SystemVersion::getCodeVersion()).
                "<br><br>
                <a href='install.php?update=1' class='btn btn-primary'>".Text::installNextStep()."</a>";
    }
    echo "  </div>
	    </div>";
} elseif ( SystemVersion::isUpgradeNeeded() ) {
    $link = GITHUB_PROJECT_LINK."/archive/main.tar.gz";
    $autoUpdater = new SelfUpdateCode();
    echo "<div class='card mb-4 shadow'>
	        <div class='card-header'>".Text::installUpdateHeader()."</div>
            <div class='card-body overflow-auto'>";
    if ( isset($_GET['update'] ) ) {
        $result = $autoUpdater->execute();
        if ( $result === true ) {
            echo Text::installAutoUpgradeSuccess().
                "<br><br>
                <a href='install.php' class='btn btn-primary'>".Text::installNextStep()."</a>";
        } else {
            echo Text::installAutoUpgradeFail($result).
                "<br>".
                Text::installUpgradeBody($link, $link).
                "<br><br>
                <a href='install.php' class='btn btn-primary'>".Text::installNextStep()."</a>";
        }
    } else {
        if ($autoUpdater->checkPrepareSteps()) {
            echo Text::installAutoUpgradeBody();
        } else {
            echo Text::installUpgradeBody($link, $link);
        }
        echo "  <br><br>
                <a href='install.php?update=1' class='btn btn-primary'>".Text::installNextStep()."</a>";
    }
    echo "  </div>
	    </div>";
} else {
    echo "<div class='alert alert-success' role='alert'>
            ".Text::installFinish()."
            <br><br>
            <a href='/' class='btn btn-primary'>".Text::installTrySystem()."</a>
    </div>";
}

echo $page->getPageFooter();