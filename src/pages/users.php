<?php

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

require_once(__DIR__."/../autoload.php");

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

if ( isset($_POST['value']) ) {
    $value = htmlspecialchars($_POST['value']);
    list($user_id, $name) = explode('_', $_POST['field'], 2);
    $user = new User($user_id);
    if ( $user->user_id > 0 ) {
        $user->$name = $value;
        $user->saveToDatabase();
    }
    exit();
}
if ( isset($_GET['user']) ) {
    switch ($_GET['user']) {
        case 'create':
            $user = new User();
            $user->language = $authorizationAdmin->getUser()->language;
            $user->saveToDatabase();
            break;
        case 'block':
            $user = new User((int)$_GET['user_id']);
            $user->blockUser();
            break;
        case 'unblock':
            $user = new User((int)$_GET['user_id']);
            $user->unblockUser();
            break;
        case 'loginLink':
            $user = new User((int)$_GET['user_id']);
            if ( $user->user_id > 0 ) {
                $token = $authorizationAdmin->getTokenForUser($user->user_id);
                $_SESSION['result_message'] = Text::usersAddUserSuccess($user->getHTMLCaption(), SERVER_HOST.'src/pages/?token='.$token);
            }
            break;
    }
    Header("Location: users.php");
    exit();
}

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::usersPageHeader());

if ( isset($_SESSION['result_message']) ) {
    echo "<div class='alert alert-success' role='alert'>".$_SESSION['result_message']."</div>";
    unset($_SESSION['result_message']);
}

function showInput($id, $name, $value, bool $canChange, $placeholder = '', $small = false): string {
    $value = str_replace('"', '&quot;', $value);
    return "<input class='form-control' type='text' id='".$id."_".$name."' value=\"$value\" ".($canChange?'':'disabled')." placeholder='$placeholder' ".($small?"style='width:90px'":'').">";
}

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>".Text::usersPageHeader()."</div>
        <form action='' method='POST'>
            <div class='card-body overflow-auto'>
                <table class='table table-sm table-hover table-responsive-lg admin-users tablesorter'>
                    <thead>
                      <tr>
                          <th>ID</th>
                          <th>E-mail</th>
                          <th>".Text::surnameName()."</th>
                          <th>".Text::status()."</th>
                          <th>".Text::actions()."</th>
                      <tr>
                    </thead> 
                    <tbody>";
$query = DB::prepare("SELECT * FROM users");
$query->execute(array());
while ($row = $query->fetch()) {
    $user = new User($row);
    if ( $user->isOnline() ) {
        $intervalFromLastAction = 0;
        $intervalFromLastActionText = "<span class='badge bg-success'>online</span>";
    } else {
        $intervalFromLastAction = $user->getIntervalFromLastAction();
        if ($intervalFromLastAction > ONE_YEAR) {
            $intervalFromLastActionText = "<i>inactive</i>";
        } else {
            $intervalFromLastActionText = Text::usersLastActionTime(getIntervalRoundLength($intervalFromLastAction));
        }
    }
    if ( $user->isBlocked() ) {
        $actions = "<a href='users.php?user=unblock&user_id=".$user->user_id."' class='btn btn-sm btn-danger'>unblock</a>";
        $canChange = false;
    } else {
        $actions = "<a href='users.php?user=loginLink&user_id=".$user->user_id."' class='btn btn-sm btn-warning'>link to logon</a>
                <a href='users.php?user=block&user_id=".$user->user_id."' class='btn btn-sm btn-danger'>block</a>";
        $canChange = true;
    }
    echo "<tr>
            <td>".$user->user_id."</td>
            <td>".showInput($row['user_id'], 'email', $user->email, $canChange, 'E-mail')."</td>
            <td>".showInput($row['user_id'], 'FIO', $user->FIO, $canChange, Text::SurnameName())."</td>
            <td><input type='hidden' value='".$intervalFromLastAction."'>".$intervalFromLastActionText."</td>
            <td>".$actions."</td>
          </tr>";
}
echo "            </tbody>
                </table>
            </div>
            <div class='card-footer'>
                <a href='users.php?user=create' class='btn btn-primary'>".Text::addUser()."</a>
            </div>
        </form>
    </div>";

$additionalScript = "
    <script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForUsers();
        })
    </script>";
echo $page->getPageFooter( $additionalScript );