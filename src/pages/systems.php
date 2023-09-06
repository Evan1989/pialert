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


function getSystemCard(string $description, string $addButton = ''): string {
    $result = " <form action='' method='POST' class='col-lg-4 col-md-6'>
            <div class='card mb-4 shadow'>
                <div class='card-header'>" . $description . "</div>
                <div class='card-body'>";
        $result .= "<div class='mb-3'>
                <label class='form-label'>".Text::systemsCode()."</label>
                <input class='form-control' type='text' id='code' name='".Text::systemsCode()."' maxlength='50'  placeholder='".Text::systemsCode()."'required >
            </div>
            <div class='mb-3'>
                <label class='form-label'>".Text::systemsName()."</label>
                <input class='form-control' type='text' id='name' name='".Text::systemsName()."' maxlength='100'  placeholder='".Text::systemsName()."'required>
            </div>
            <div class='mb-3'>
                <label class='form-label'>".Text::systemsContact()."</label>
                <textarea class='form-control'  id='contact' name='".Text::systemsContact()."' maxlength='1000'  placeholder='".Text::systemsContact()."'></textarea>
            </div>
            <div class='mb-3'>
                <label class='form-label'>".Text::systemsComment()."</label>
                <input class='form-control' type='text' id='comment' name='".Text::systemsComment()."' maxlength='200'  placeholder='".Text::systemsComment()."'>
            </div>       
            ";

    $result .= "</div>
                <div class='card-footer'>
                    <input class='btn btn-primary' type='submit' name='save' value='".Text::save()."'>
                    ".$addButton."
                      <input class='btn btn-danger' type='submit' name='delete' value='".Text::delete()."'> 
                    ".$addButton." 
                </div> 
            </div>
        </form>";
    return $result;
}

function saveSystem(string $code, string $name, string $contact = '', string $comment = ''): bool {

    $query = DB::prepare("SELECT code FROM bs_systems WHERE code = ?");
    $query->execute(array($code));
    if( $query->fetch() ) {
        $query = DB::prepare("UPDATE bs_systems SET code = ?, name = ?, contact = ?, comment = ? WHERE code = ?");
        return $query->execute(array($code, $name, $contact, $comment,$code));
    }
    else
    {
        $query = DB::prepare("INSERT INTO bs_systems (code, name, contact, comment) VALUES(?, ?, ?, ?)");
        return $query->execute(array($code, $name, $contact, $comment));
    }
}

function deleteSystem(string $code): bool {
    $query = DB::prepare("DELETE FROM bs_systems WHERE code = ?");
    return $query->execute(array($code));
}

function getSystems($description):string {
    $result = " <form action='' method='POST' class='col-lg-4 col-md-6'>
            <div class='card mb-4 shadow' > 
                <div class='card-header'>" . $description . "</div>
                 <div class='input-group mb-3'>
                   <input class='d-inline form-control form-control-sm' id='mainTableSearch' type='text' placeholder='".Text::search()."...' value=''>
                  </div>
             <div class='row  main-table-for-filter table-responsive' > 
             <div class='col'>
      <table class='table' id='systems' role='button'>
        <thead class='table-light'>
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
    $tmpCount = 1;
    while($row = $query->fetch()) {
         $result.=
                        "<tr>
                        <td>".$tmpCount."</td>
                        <td>".$row['code']."</td>
                        <td>".$row['name']."</td>
                        <td> ".$row['contact']."</td>
                        <td>".$row['comment']."</td>
                    </tr>";
        $tmpCount++;
    }
    $result .= "</tbody>
      </table>
         </div>
          </div>
   </div>
     </form>";
    return $result;
}


echo "<div class='row'>";

$filterValues='';
$result=false;

if ($_POST) {
    $result = true;
    $code = str_replace(' ', '_', Text::systemsCode());
    $name = str_replace(' ', '_', Text::systemsName());
    $contact = str_replace(' ', '_', Text::systemsContact());
    if(isset($_POST['save'])) {

        $result = saveSystem($_POST[$code], $_POST[$name], $_POST[$contact], $_POST[Text::systemsComment()]);
    }
        if (isset($_POST['delete'])) {
            $result=deleteSystem($_POST[$code]);
        }
}

echo getSystemCard(Text::systemCard());

echo getSystems(Text::menuSystems());

echo "</div>";

$additionalScript = " <script type='text/javascript'>
    selectSystemInfo(document);
         </script>";

echo $page->getPageFooter($additionalScript);



