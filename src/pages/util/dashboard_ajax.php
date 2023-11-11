<?php

//////////////////////////////////////////////////
//  Скрипт подтягивается в другие через include //
//////////////////////////////////////////////////

use EvanPiAlert\Util\AlertAggregationUtil;
use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\HTML\HTMLChart;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

function saveInputNewValueToAlertGroup(string $element_type, $group_id, ?string $value) : string {
    global $authorizationAdmin;
    $alertGroup = new PiAlertGroup($group_id);
    $result = '';
    if ( $alertGroup->group_id > 0 ) {
        switch ($element_type) {
            case 'user':
                if ( !is_null($value) ) {
                    $alertGroup->setUserId( (int) $value );
                }
                $result = getUserChoice($alertGroup);
                break;
            case 'status':
                if ( !is_null($value) ) {
                    $alertGroup->status = (int)$value;
                    if ( $alertGroup->status == PiAlertGroup::CLOSE ) {
                        $alertGroup->setUserId( $authorizationAdmin->getUserId() );
                        $alertGroup->setUserId( null );
                    } elseif ( $alertGroup->status == PiAlertGroup::WAIT && is_null($alertGroup->user_id) ) {
                        $alertGroup->setUserId( $authorizationAdmin->getUserId() );
                    }
                }
                $result = getStatusChoice($alertGroup);
                break;
            case 'comment':
                if ( !is_null($value) ) {
                    $alertGroup->setComment($value);
                }
                $result = getComment($alertGroup);
                break;
            case 'alertLink':
                if ( !is_null($value) ) {
                    $alertGroup->setAlertLink($value);
                }
                $result = getAlertLink($alertGroup);
                break;
        }
        if ( !is_null($value) ) {
            $alertGroup->saveToDatabase();
        }
    }
    return $result;
}
function getAlertGroupFullInfo(AuthorizationAdmin $authorizationAdmin, int $group_id) : string {
    $alertGroup = new PiAlertGroup($group_id);
    $user = new User($alertGroup->user_id);
    $first_alert = strtotime($alertGroup->firstAlert);
    $result = "<table class='table table-sm table-hover'>
                <tbody>
                      <tr>
                          <td>".Text::status()."</td>
                          <td class='bg-".$alertGroup->getStatusColor($authorizationAdmin->getUserId())."'>".PiAlertGroup::getStatusName($alertGroup->status)."</td>
                      </tr>
                      <tr>
                          <td>".Text::responsibleEmployee()."</td>
                          <td class='bg-".$alertGroup->getUserColor($authorizationAdmin->getUserId())."'>".$user->getAvatarImg('alert-more-info-user-avatar').$user->getHTMLCaption('-')."</td>
                      </tr>
                      <tr>
                          <td>".Text::comment()."</td>
                          <td>".$alertGroup->getHTMLComment()."</td>
                      </tr>
                      <tr>
                          <td>".Text::requestList()."</td>
                          <td>".$alertGroup->getHTMLAlertLink()."</td>
                      </tr>
                      <tr>
                          <td>".Text::dashboardRequisites()."</td>
                          <td>".$alertGroup->getHTMLAbout()."</td>
                      </tr>
                      <tr>
                          <td>".Text::dashboardMaskOrError()."</td>
                          <td>".$alertGroup->getHTMLErrorTextMask()."</td>
                      </tr>
                      <tr>
                          <td>".Text::dashboardFirstAlert()."</td>
                          <td>".$alertGroup->firstAlert." (".getIntervalRoundLength(time()-strtotime($alertGroup->firstAlert)).")</td>
                      </tr>
                      <tr>
                          <td>".Text::dashboardLastAlert()."</td>
                          <td>".$alertGroup->lastAlert." (".getIntervalRoundLength(time()-strtotime($alertGroup->lastAlert)).")</td>
                      </tr>
                      <tr>
                          <td>".Text::statisticAlert24HourCount()."</td>
                          <td>".$alertGroup->getAlertCount(ONE_DAY )." ".Text::pieces()."</td>
                      </tr>";
    if ( time() - $first_alert > ONE_WEEK ) {
        $week_alert = $alertGroup->getAlertCount(ONE_WEEK);
        $result.= "   <tr>
                          <td>".Text::statisticAlertWeekCount()."</td>
                          <td>".$week_alert." ".Text::pieces().($week_alert>0?' ≈ '.round10($week_alert/7).' '.Text::perDay():'')."</td>
                      </tr>";
        if ( time() - $first_alert > ONE_MONTH ) {
            $month_alert = $alertGroup->getAlertCount(ONE_MONTH);
            $result.= "<tr>
                          <td>".Text::statisticAlertMonthCount()."</td>
                          <td>".$month_alert." ".Text::pieces().($month_alert>0?' ≈ '.round10($month_alert/30.5).' '.Text::perDay():'')."</td>
                      </tr>";
        }
    }
    $result.= "        <tr>
                          <td>".Text::statisticAlertTotalCount()."</td>
                          <td>".$alertGroup->getAlertCount()." ".Text::pieces()."</td>
                      </tr>";
    if ( strtotime($alertGroup->lastAlert) - $first_alert > ONE_DAY ) {
        $chart = new HTMLChart();
        $result.= "   <tr>
                            <td>".Text::statisticAlertMonthChart()."</td>
                            <td>".$chart->getDailyAlertsChart( $alertGroup )."</td>
                      </tr>";
    }
    $result.= "       <tr>
                          <td>".Text::actions()."</td>
                          <td>
                            <a href=\"javascript:loadAlertsForGroup(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='top' title='".Text::dashboardShowAlertButton()."'>".HTMLPageTemplate::getIcon('envelope')."</a>
                            <a href='dashboard.php?id=".$alertGroup->group_id."' data-toggle='tooltip' data-placement='top' title='".Text::dashboardShareLinkButton()."'>".HTMLPageTemplate::getIcon('share')."</a>
                            <a href='dashboard.php?id=".$alertGroup->group_id."&showSameErrors' data-toggle='tooltip' data-placement='top' title='".Text::dashboardFindSameErrors()."'>".HTMLPageTemplate::getIcon('magic')."</a>
                          </td>
                      </tr>
                </tbody>
              </table>";
    return $result;
}

function getUnionAlertGroupForm(HTMLPageTemplate $page, AuthorizationAdmin $authorizationAdmin, int $group_id) : string {
    $alertGroup = new PiAlertGroup($group_id);
    $variants = AlertAggregationUtil::getMaybeSimilarAlertGroup($alertGroup);
    $result = "<table class='table table-sm table-hover'>
                <thead>
                    <tr>
                        <th>".Text::status()."</th>
                        <th>".Text::object()."</th>
                        <th>".Text::comment()."</th>
                        <th>".Text::dashboardMaskOrError()."</th>
                        <th>".Text::dashboardMaskAfterUnion()."</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class='bg-".$alertGroup->getStatusColor($authorizationAdmin->getUserId())."'>".PiAlertGroup::getStatusName($alertGroup->status)."</td>
                        <td>".$alertGroup->getHTMLAbout()."</td>
                        <td>".$alertGroup->getHTMLComment()."</td>
                        <td>".$alertGroup->getHTMLErrorTextMask()."</td>
                        <td>-</td>
                        <td>".Text::dashboardUnionGroupButtonStep2()."</td>
                    </tr>";
    foreach ($variants as $mask => $altAlertGroup) {
        /** @var PiAlertGroup $altAlertGroup */
        $result .= "<tr>
                        <td class='bg-".$altAlertGroup->getStatusColor($authorizationAdmin->getUserId())."'>".PiAlertGroup::getStatusName($altAlertGroup->status)."</td>
                        <td>".$altAlertGroup->getHTMLAbout()."</td>
                        <td>".$altAlertGroup->getHTMLComment()."</td>
                        <td>".$altAlertGroup->getHTMLErrorTextMask()."</td>
                        <td>".str_replace('*', "<span class='text-danger'>*</span>", $mask)."</td>
                        <td>
                            <a href='javascript:unionAlertGroupStep2(".$alertGroup->group_id.",".$altAlertGroup->group_id.")'>".Text::dashboardThisGroup()."</a>
                            <a href=\"javascript:loadAlertGroupFullInfo(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='left' title='".Text::dashboardShowStatisticButton()."'>".$page->getIcon('graph-up')."</a>
                        </td>
                    </tr>";
    }
    $result .= "</tbody>
           </table>";
    return $result;
}

function getUnionAlertGroupResult(int $group_id_from, int $group_id_to) : string {
    $alertGroupFrom = new PiAlertGroup($group_id_from);
    $alertGroupTarget = new PiAlertGroup($group_id_to);
    if (in_array($alertGroupTarget, AlertAggregationUtil::getMaybeSimilarAlertGroup($alertGroupFrom) ) ) {
        $result = AlertAggregationUtil::unionAlertGroups($alertGroupFrom, $alertGroupTarget);
        if ( $result ) {
            return Text::dashboardUnionSuccess()."
                    <br>
                    <br>
                    <a href='javascript:dashboardPageReload()' class='btn btn-primary btn-sm'>".Text::reloadPage()."</a>";
        }
        return "<div class='badge bg-danger'>".Text::dashboardUnionFail()."</div>";
    }
    return "<div class='badge bg-danger'>".Text::dashboardUnionFatalError()."</div>";
}