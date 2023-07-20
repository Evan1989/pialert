<?php

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\SystemVersion;
use EvanPiAlert\Util\Text;
use JetBrains\PhpStorm\Pure;

require_once(__DIR__ . "/../autoload.php");

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

const DEFAULT_PASSWORD = "********";

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::menuSettings());

if ($_POST) {
    $result = true;
    foreach ($_POST as $code => $value) {
        $code = str_replace('_', ' ', $code);
        if ($value !== DEFAULT_PASSWORD) {
            if (!Settings::set($code, $value)) {
                $result = false;
            }
        }
    }
    if ($result) {
        echo "<div class='alert alert-success' role='alert'>".Text::settingsUpdateSuccess()."</div>";
    } else {
        echo "<div class='alert alert-error' role='alert'>".Text::settingsUpdateFail()."</div>";
    }
}

function getSettingTextareaDiv($name, $value): string {
    return "<div class='mb-3'>
                <label class='form-label'>" . $name . "</label>
                <textarea class='form-control' name='" . $name . "' rows='3' placeholder='" . $name . "'>" . $value . "</textarea>
            </div>";
}

#[Pure] function getSettingInputDiv($name, $value, $type = 'text'): string {
    $disabled = false;
    switch ($type) {
        case 'blocked':
            $type = 'text';
            $disabled = true;
            break;
        case 'password':
            $value = DEFAULT_PASSWORD;
            break;
        case 'textarea':
            return getSettingTextareaDiv($name, $value);
    }
    return "<div class='mb-3'>
                <label class='form-label'>".$name."</label>
                <input class='form-control' type='".$type."' name='".$name."' maxlength='1000' value='".$value."' placeholder='".$name."' ".($disabled?'disabled':'').">
            </div>";
}

function getBigSettingGroup($description, $fields): string {
    $result = " <form action='' method='POST' class='col-lg-8 col-md-12'>
            <div class='card mb-4 shadow'>
                <div class='card-header'>" . $description . "</div>
                <div class='card-body row'>
                    <div class='col-md-6'>";
    foreach ($fields as $num => $field) {
        if ($num == 3) {
            $result .= "</div>
                    <div class='col-md-6'>";
        }
        $result .= getSettingInputDiv($field[0], $field[1], $field[2]);
    }
    $result .= "    </div>
                </div>
                <div class='card-footer'>
                    <input class='btn btn-primary' type='submit' value='".Text::save()."'>
                </div>
            </div>
        </form>";
    return $result;
}

function getSmallSettingGroup($description, $fields, string $addButton = ''): string {
    $result = " <form action='' method='POST' class='col-lg-4 col-md-6'>
            <div class='card mb-4 shadow'>
                <div class='card-header'>" . $description . "</div>
                <div class='card-body'>";
    foreach ($fields as $field) {
        $result .= getSettingInputDiv($field[0], $field[1], $field[2]);
    }
    $result .= "</div>
                <div class='card-footer'>
                    <input class='btn btn-primary' type='submit' value='".Text::save()."'>
                    ".$addButton."
                </div>
            </div>
        </form>";
    return $result;
}

function getSettingGroup($groupCode, $description): string {
    $fields = array();
    $query = DB::prepare("SELECT * FROM settings WHERE grp = ?");
    $query->execute(array($groupCode));
    while ($row = $query->fetch()) {
        $fields[] = array(
            $row['code'],
            $row['value'],
            $row['type']
        );
    }
    if (!$fields) {
        return '';
    }
    $result = '';
    while (!empty($fields)) {
        if (count($fields) > 4) {
            $result .= getBigSettingGroup(
                $description,
                array_slice($fields, 0, 6)
            );
        } else {
            $result .= getSmallSettingGroup($description, $fields);
        }
        $fields = array_slice($fields, 6);
    }
    return $result;
}

echo "<div class='row'>";

if ( SystemVersion::isFinishInstallNeeded() || SystemVersion::isUpgradeNeeded() ) {
    $addButton = "<a href='install.php' class='btn btn-success'>".Text::settingsInstallButton()."</a>";
} else {
    $addButton = '';
}

$githubVersion = SystemVersion::getGithubVersion();
$systemFields = array(
    array(Text::settingsHostname(), SERVER_HOST, 'blocked'),
    array(Text::settingsCodeVersion(), SystemVersion::getCodeVersion(), 'blocked'),
    array(Text::settingsDataBaseVersion(), SystemVersion::getDatabaseVersion().'.*', 'blocked'),
    array(Text::settingsGithubVersion(), $githubVersion?:GITHUB_PROJECT_LINK, 'blocked')
);
echo getSmallSettingGroup(Text::settingsCommonSettings().' PiAlert', $systemFields, $addButton);
foreach (Settings::getSettingGroups() as $code => $description) {
    echo getSettingGroup($code, $description);
}
echo "</div>";

echo $page->getPageFooter();
