<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::systemsPageHeader());

function saveSystem(string $prev_code, string $code, string $name, string $contact, string $comment): bool {
    $query = DB::prepare("SELECT * FROM bs_systems WHERE code = ?");
    $query->execute(array($prev_code));
    if ( $query->fetch() ) {
        $query = DB::prepare("UPDATE bs_systems SET code = ?, name = ?, contact = ?, comment = ? WHERE code = ?");
        return $query->execute(array($code, $name, $contact, $comment, $prev_code));
    }
    $query = DB::prepare("INSERT INTO bs_systems (code, name, contact, comment) VALUES(?, ?, ?, ?)");
    return $query->execute(array($code, $name, $contact, $comment));
}

function deleteSystem(string $code): bool {
    $query = DB::prepare("DELETE FROM bs_systems WHERE code = ?");
    return $query->execute(array($code));
}

if ( $_POST ) {
    if( isset($_POST['save']) ) {
        saveSystem($_POST['prev_code'], $_POST['code'], $_POST['name'], $_POST['contact'], $_POST['comment']);
    }
    if (isset($_POST['delete'])) {
        deleteSystem($_POST['prev_code']);
    }
}

echo "<div class='row'>
        <form class='col-lg-4 col-md-4' method='POST'>
            <div class='card mb-4 shadow'>
                <div class='card-header'>".Text::systemCard()."</div>
                <div class='card-body'>
                    <input type='hidden' id='prev_code' name='prev_code'>
                    <div class='mb-3'>
                        <input class='form-control' type='text' id='code' name='code' maxlength='50'  placeholder='".Text::systemsCode()."'required >
                    </div>
                    <div class='mb-3'>
                        <input class='form-control' type='text' id='name' name='name' maxlength='100'  placeholder='".Text::systemsName()."'required>
                    </div>
                    <div class='mb-3'>
                        <textarea class='form-control'  id='contact' name='contact' maxlength='1000'  placeholder='".Text::systemsContact()."'></textarea>
                    </div>
                    <div class='mb-3'>
                        <textarea class='form-control'  id='comment' name='comment' maxlength='1000'  placeholder='".Text::systemsComment()."'></textarea>
                    </div>    
                </div>
                <div class='card-footer'>
                    <input class='btn btn-primary' type='submit' name='save' value='".Text::save()."'>
                    <input class='btn btn-danger' type='submit' name='delete' value='".Text::delete()."'>
                </div> 
            </div>
        </form>
        <div class='col-lg-8 col-md-8'>
            <div class='card mb-4 shadow'> 
                <div class='card-header'>
                    ".Text::menuSystems()."
                     <div class='float-end mx-2'>
                        <input class='d-inline form-control form-control-sm' id='mainTableSearch' type='text' placeholder='".Text::search()."...' value=''>
                     </div>
                </div>
                <div class='card-body'>
                  <table class='table main-table-for-filter table-responsive tablesorter' id='systems'>
                    <thead>
                      <tr>
                        <th>№</th>
                        <th>".Text::systemsCode()."</th>
                        <th>".Text::systemsName()."</th>
                        <th>".Text::systemsContact()."</th>
                        <th>".Text::systemsComment()."</th>
                      </tr>
                    </thead>
                    <tbody>";
$query = DB::prepare("SELECT * FROM bs_systems");
$query->execute();
$number = 1;
while($row = $query->fetch()) {
     echo "            <tr role='button'>
                            <td>".$number."</td>
                            <td>".$row['code']."</td>
                            <td>".$row['name']."</td>
                            <td>".nl2br($row['contact'])."</td>
                            <td>".nl2br($row['comment'])."</td>
                       </tr>";
    $number++;
}
echo "              </tbody>
                  </table>
               </div>
            </div>
        </div>
   </div>";

$additionalScript = "
    <script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForSystems(document);
        });
    </script>";
echo $page->getPageFooter($additionalScript);



