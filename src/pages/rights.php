<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

const DEFAULT_MENU_ID = 1;

$adminsRights = array();
$groups = array();
$pages = array();
$lastGroup = false;
$query = DB::prepare("
    SELECT *
    FROM user_rights as r
        LEFT JOIN users u on r.user_id = u.user_id
        LEFT JOIN pages p on r.menu_id = p.menu_id
    WHERE page_caption IS NOT NULL
    ORDER BY p.number");
$query->execute(array());
while ($row = $query->fetch()) {
    if ( $row['user_id'] == $authorizationAdmin->getUserId() ) {
        if ( $row['group_caption'] ) {
            if ( $lastGroup == $row['group_caption'] ) {
                $groups[ $row['group_caption'] ] += 1;
            } else {
                $groups[ $row['group_caption'] ] = 1;
            }
        } else {
            $groups[ $row['page_caption'] ] = -1;
            $row['page_caption'] = '';
        }
        $pages[ $row['menu_id'] ] = $row['page_caption'];
        $lastGroup = $row['group_caption'];
    }
    $adminsRights[ $row['user_id'] ]['user'] = $row;
    $adminsRights[ $row['user_id'] ]['pages'][$row['menu_id']] = true;
}
if ( $_POST ) {
    if ( isset($_POST['newUser']) ) {
        $search = $_POST['newUser'];
        $user_id = false;
        $query = DB::prepare("SELECT * FROM users WHERE email like ? OR FIO like ?");
        $query->execute(array('%'.$search.'%', '%'.$search.'%'));
        if ($row = $query->fetch()) {
            $user_id = $row['user_id'];
        }
        if ( $user_id ) {
            $query = DB::prepare("INSERT INTO user_rights (user_id, menu_id) VALUES (?, ?)");
            $query->execute(array($row['user_id'], DEFAULT_MENU_ID));
            Header("Location: rights.php");
            exit();
        }
    } else {
        $value = $_POST['value'];
        list($user_id, $menu_id) = explode('_', $_POST['right']);
        // Есть ли у нас есть такая страница
        if (isset($pages[$menu_id])) {
            // Есть ли у нас такой пользователь
            $query = DB::prepare("SELECT * FROM users WHERE user_id = ?");
            $query->execute(array($user_id));
            if ($row = $query->fetch()) {
                if ($_POST['value'] == 'true') {
                    $query = DB::prepare("INSERT INTO user_rights (user_id, menu_id) VALUES (?, ?)");
                } else {
                    $query = DB::prepare("DELETE FROM user_rights WHERE user_id = ? AND menu_id = ?");
                }
                $query->execute(array($user_id, $menu_id));
            }
        }
        exit();
    }
}

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::menuRights());

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>".Text::menuRights()."</div>
        <div class='card-body overflow-auto'>
            <table class='tablesorter table table-sm table-hover table-responsive-lg admin-rights'>
                <thead>
                  <tr>
                      <th rowspan='2'>".Text::user()."</th>";
foreach ($groups as $caption => $count ) {
    if ( $count == -1) {
        echo "<th rowspan='2'>".Text::$caption()."</th>";
    } else {
        echo "<th colspan='".$count."'>".Text::$caption()."</th>";
    }
}
echo "            </tr>
                  <tr>";
foreach ($pages as $menu_id => $caption ) {
    if ( $caption ) {
        echo "<th>".Text::$caption()."</th>";
    }
}
echo "            </tr>
                </thead> 
                <tbody>";
foreach ( $adminsRights as $user_id => $data ) {
    $user = new User($data['user']);
    echo "<tr>
            <td>".$user->getAvatarImg('rights-user-avatar').$user->getHTMLCaption()."</td>";
    foreach ($pages as $menu_id => $temp ) {
        echo "<td class='center'>
                <input type='checkbox' class='form-check-input' id='".$user_id."_".$menu_id."'";
        if ( $data['pages'][$menu_id] ?? false ) {
            echo ' checked';
        }
        if ( $user_id==$authorizationAdmin->getUserId() ) {
            echo ' disabled';
        }
        echo ">
            </td>";
    }
    echo "</tr>";
}
echo "          </tbody>
            </table>
        </div>
        <div class='card-footer'>
            <form action='' method='POST'>
                <div class='mb-3'>
                    <input class='form-control' type='text' name='newUser' maxlength='100' value='' placeholder='".Text::user()."' data-placement='top' data-toggle='tooltip' title='".Text::surnameName()." ".Text::or()." e-mail' required>
                </div>
                <input class='btn btn-primary' type='submit' value='".Text::addUser()."'>
            </form>    
        </div>
    </div>";

$additionalScript = "
    <script type='text/javascript'>
        $(document).ready(function() {
           $(\".admin-rights input[type='checkbox']\").change(function(){
              $(this).attr('disabled', 'disabled');
              let id = $(this).attr('id');
              $.ajax({
                type: 'POST',
                url: 'rights.php',
                data: 'right='+id+'&value='+$(this).prop('checked'),
                'success': function(data) {
                    $('#'+id).removeAttr('disabled');
                    console.log(data);
                }
            });
           });
        })
    </script>";
echo $page->getPageFooter( $additionalScript );
