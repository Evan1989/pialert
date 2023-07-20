<?php

namespace EvanPiAlert\Util\HTML;

use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\Text;
use PDOStatement;

class HTMLPageAlerts extends HTMLPageTemplate {

    /**
     * Отобразить таблицу с данными извлеченными из запроса
     * @param PDOStatement $query Execute уже должен быть выполнен
     * @return string
     */
    public function getAlertTable(PDOStatement $query) : string {
        /** @var PiAlert[] $alerts */
        $alerts = array();
        $sender = false;
        $receiver = false;
        $UDS = false;
        while($row = $query->fetch()) {
            $alert = new PiAlert($row);
            if ( $alert->fromSystem ) $sender = true;
            if ( $alert->toSystem ) $receiver = true;
            if ( $alert->UDSAttributes ) $UDS = true;
            $alerts[] = $alert;
        }
        $result = "<table class='tablesorter table table-sm table-hover table-responsive-lg alert-table'>
            <thead>
              <tr>
                  <th>".Text::date()."</th>
                  ".($sender?"<th>".Text::sender()."</th>":"")."
                  ".($receiver?"<th>".Text::receiver()."</th>":"")."
                  <th>".Text::object()."</th>
                  <th>MessageId</th>
                  ".($UDS?"<th>UDS</th>":"")."
                  <th>".Text::error()."</th>
              </tr>
            </thead> 
            <tbody>";
        foreach ($alerts as $alert) {
            $result .= "<tr>
                    <td>".$alert->timestamp."</td>
                    ".($sender?"<td><div class='alert-limit-td'>".$alert->fromSystem."</div></td>":"")."
                    ".($receiver?"<td><div class='alert-limit-td'>".$alert->toSystem."</div></td>":"")."
                    <td data-toggle='tooltip' data-placement='auto' title='".$alert->namespace."'><div class='alert-limit-td'>".$alert->getObject()."</div></td>
                    <td><div class='alert-limit-td'>".$alert->getHTMLMessageId()."</div></td>
                    ".($UDS?"<td><div class='alert-limit-td'>".$alert->UDSAttributes."</div></td>":"")."
                    <td ".($alert->errCategory.$alert->errCode?"data-toggle='tooltip' data-placement='auto'  title='".$alert->errCategory.PHP_EOL.$alert->errCode."'":"")."><div class='alert-limit-td large'>".$alert->getHTMLErrorText()."</div></td>
                </tr>";
        }
        return $result."</tbody>
            </table>";
    }

}