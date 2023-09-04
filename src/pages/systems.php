<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTML\HTMLPageAlerts;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$page = new HTMLPageAlerts($authorizationAdmin);
echo $page->getPageHeader(Text::systemsPageHeader());


function getSystemCard($description, $fields, string $addButton = ''): string {
    $result = " <form action='' method='POST' class='col-lg-4 col-md-6'>
            <div class='card mb-4 shadow'>
                <div class='card-header'>" . $description . "</div>
                <div class='card-body'>";
    $disabled = false;
    foreach ($fields as $field) {
        $result .= "<div class='mb-3'>
                <label class='form-label'>".$field[1]."</label>
                <input class='form-control' type='".$field[3]."' id='$field[0]' name='".$field[1]."' maxlength='1000' value='".$field[2]."' placeholder='".$field[1]."' ".($disabled?'disabled':'').">
            </div>";
    }
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

    $query = DB::prepare("SELECT id FROM systems WHERE code = ? AND name = ?");
    $query->execute(array($code, $name));
    if( $query->fetch() ) {
        $query = DB::prepare("UPDATE systems SET code = ?, name = ?, contact = ?, comment = ? WHERE code = ? AND name = ?");
        return $query->execute(array($code, $name, $contact, $comment,$code, $name));
    }
    else
    {
        $query = DB::prepare("INSERT INTO systems (code, name, contact, comment) VALUES(?, ?, ?, ?)");
        return $query->execute(array($code, $name, $contact, $comment));
    }
}

function deleteSystem(string $code, string $name): bool {
    $query = DB::prepare("DELETE FROM systems WHERE code = ? AND name= ?");
    return $query->execute(array($code, $name));
}

function getSystems($description,$filterValues):string {
    $result = " <form action='' method='POST' class='col-lg-4 col-md-6' style='width: 50%'>
            <div class='card mb-4 shadow'>
                <div class='card-header'>" . $description . "</div>
                 <div class='input-group mb-3'>
                  <input type='text' name='search_system' class='form-control'>
                  <input class='btn btn-primary' type='submit' name='search' value='".Text::search()."'>
                  </div>
                <div class='card-body' style='overflow:scroll; height: 380px; width: 100%;'>
                <div class='table-responsive'>          
      <table class='table' id='systems'>
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

    $query = DB::prepare("SELECT * FROM systems WHERE CONCAT(code,name,contact) LIKE '%' ? '%'");
    $query->execute(array($filterValues));
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
      <script>
    
                var table = document.getElementById('systems');
                
                for(var i = 1; i < table.rows.length; i++)
                {
                    table.rows[i].onclick = function()
                    {
                       //  rIndex = this.rowIndex;
                         document.getElementById('code').value = this.cells[1].innerHTML;
                        document.getElementById('name').value = this.cells[2].innerHTML;
                        document.getElementById('contact').value = this.cells[3].innerHTML;
                       document.getElementById('comment').value = this.cells[4].innerHTML;
                    };
                }
    
         </script>
    </div>
    </div>";
    return $result;
}
$systemFields = array(
    array('code',Text::systemsCode() ,'','text'),
    array('name',Text::systemsName(), '','text'),
    array('contact',Text::systemsContact(), '','text'),
    array('comment',Text::systemsComment(), '','text'),
    );

echo "<div class='row'>";

$filterValues='';
$result=false;

if ($_POST) {
    if(isset($_POST['search'])) {
        $filterValues = $_POST['search_system'];
    }
    else {
        $result = true;
        $code = str_replace(' ', '_', Text::systemsCode());
        $name = str_replace(' ', '_', Text::systemsName());
        $contact = str_replace(' ', '_', Text::systemsContact());
        $result=saveSystem($_POST[$code], $_POST[$name], $_POST[$contact], $_POST[Text::systemsComment()]);
        if (isset($_POST['delete'])) {
            $result=deleteSystem($_POST[$code], $_POST[$name]);
        }
    }
}

echo getSystemCard(Text::systemCard(), $systemFields);

echo getSystems(Text::menuSystems(), $filterValues);

echo "</div>";

echo $page->getPageFooter();



