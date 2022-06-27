<?php

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

require_once(__DIR__."/../autoload.php");

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$user = $authorizationAdmin->getUser();
if ( isset($_POST['newPassword']) ) {
    $newPassword = $_POST['newPassword'];
    $newPasswordResult = $user->setNewPassword($newPassword);
}
if ( isset($_GET['newLanguage']) ) {
    $user->language = Text::language($_GET['newLanguage']);
    $user->saveToDatabase();
}

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::menuProfile());

$query = DB::prepare(" SELECT count(*) as c FROM alert_group WHERE user_id = ?");
$query->execute(array($user->user_id));
$row = $query->fetch();
$alertGroupCount = $row['c'];

if ( isset($newPasswordResult) ) {
    if ( $newPasswordResult ) {
        echo "<div class='alert alert-success' role='alert'>".Text::profilePasswordChangeSuccess()."</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>".Text::profilePasswordChangeFail()."</div>";
    }
}

function showLanguageForm(?string $userLanguage) : string {
    $result = "";
    foreach (Text::LANGUAGES as $language) {
        $result .= "<option value='".$language."' ".($language==$userLanguage?'selected':'').">".$language."</option>";
    }
    return "<select class='form-control-plaintext profile-change-language'>".$result."</select>";
}

$query = DB::prepare("
    SELECT sum(seconds) as seconds
    FROM user_statistic_online
    WHERE user_id = ?
");
$query->execute(array( $user->user_id ));
$secondsOnline = 0;
if ($row = $query->fetch()) {
    $secondsOnline = $row['seconds'];
}

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>".Text::profilePageHeader()."</div>
        <div class='card-body overflow-auto'>
            <table class='table table-sm table-hover table-responsive-sm tablesorter'>
                <tbody>
                    <tr>
                        <td>User ID</td>
                        <td>".$user->user_id."</td>
                    </tr>
                    <tr>
                        <td>".Text::surnameName()."</td>
                        <td>".$user->FIO."</td>
                    </tr>
                    <tr>
                        <td>".Text::profileLanguage()."</td>
                        <td>
                            <div class='col-md-3'>
                                ".showLanguageForm($user->language)."
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>E-mail</td>
                        <td>".$user->email."</td>
                    </tr>
                    <tr>
                        <td>".Text::statisticAlertGroupCount()."</td>
                        <td>".$alertGroupCount.($alertGroupCount>0?" <a href='/src/pages/dashboard.php?search=".urlencode($user->getCaption())."'>".$page->getIcon('eye')."</a>":'')."</td>
                    </tr>
                    <tr>
                        <td>".Text::profileTotalOnline()."</td>
                        <td>".($secondsOnline>ONE_DAY?getIntervalRoundLength($secondsOnline, 'hour').' ≈ ':'').getIntervalRoundLength($secondsOnline)."</td>
                    </tr>
                    <tr>
                        <td>".Text::profileChangePassword()."</td>
                        <td>
                            <form action='' method='POST' class='row g-3'>  
                                <div class='col-md-3'>
                                    <input class='form-control' type='password' name='newPassword' maxlength='100' value='' placeholder='".Text::profileNewPassword()."' data-placement='top' required>
                                </div> 
                                <div class='col-md-3'>
                                    <input class='btn btn-primary' type='submit' value='".Text::change()."'>
                                </div> 
                            </form>    
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>";

$additionalScript = "
    <script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForProfile();
        })
    </script>";
echo $page->getPageFooter( $additionalScript );