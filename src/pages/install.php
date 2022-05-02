<?php

use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\HTMLPageTemplate;
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

function executeScriptInDataBase(int $scriptNumber) : bool|string {
    $file = __DIR__.'/../install/database_'.$scriptNumber.'.sql';
    if ( is_file($file) === false ) {
        return Text::installDatabaseUpdateError($scriptNumber);
    }
    try {
        DB::exec(file_get_contents($file));
    } catch(PDOException $e) {
        return $e->getMessage();
    }
    return true;
}

if ( Settings::get(Settings::DATABASE_VERSION) === false ) {
    echo "<div class='card mb-4 shadow'>
	        <div class='card-header'>".Text::installStep2Header()."</div>
            <div class='card-body overflow-auto'>";
    if ( isset($_GET['create'] ) ) {
        $result = executeScriptInDataBase(1);
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
} elseif ( Settings::VERSION != Settings::get(Settings::DATABASE_VERSION) ) {
    echo "<div class='card mb-4 shadow'>
	        <div class='card-header'>".Text::installUpdateHeader()."</div>
            <div class='card-body overflow-auto'>";
    if ( isset($_GET['update'] ) ) {
        for ($version = Settings::get(Settings::DATABASE_VERSION)+1; $version <= Settings::VERSION; $version++) {
            $result = executeScriptInDataBase($version);
            if ($result === true) {
                echo "<div class='alert alert-success' role='alert'>".Text::installUpdateSuccess($version)."</div>";
            } else {
                echo "  <div class='alert alert-danger' role='alert'>".Text::installError($result)."</div>";
                break;
            }
        }
        echo "  <br><br>
                <a href='settings.php' class='btn btn-primary'>".Text::back()."</a>";
    } else {
        echo Text::installUpdateBody(Settings::VERSION).
                "<br><br>
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