<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\essence\PiAlertGroup;

class AlertAggregationUtil {

    /**
     * Добавить алерт в существующую группу, либо создать новую группу
     * @param PiAlert $alert
     * @return PiAlertGroup
     */
    public static function createOrFindGroupForAlert(PiAlert $alert) : PiAlertGroup {
        $maybe_need_union = 0;
        $query = DB::prepare("SELECT *  FROM alert_group WHERE piSystemName=? AND fromSystem=? AND toSystem=? AND (channel=? OR interface=?)");
        $query->execute(array($alert->piSystemName, $alert->fromSystem??'', $alert->toSystem??'', $alert->channel??'', $alert->interface??''));
        while($row = $query->fetch()) {
            $alertGroup = new PiAlertGroup($row);
            if ( TextAnalysisUtil::isSimilarText($alertGroup->errText, $alert->errText) || TextAnalysisUtil::isTextFitToMask($alert->errText, $alertGroup->errTextMask) ) {
                if ($alertGroup->status == PiAlertGroup::CLOSE) {
                    $alertGroup->status = PiAlertGroup::REOPEN;
                }
                $alertGroup->lastAlert = $alert->timestamp;
                if ( $alertGroup->interface != $alert->interface ) {
                    $alertGroup->multi_interface = 1;
                }
                $alertGroup->saveToDatabase();
                return $alertGroup;
            }
            if ( !is_null(TextAnalysisUtil::getMaskFromTexts($alert->errText, $alertGroup->errTextMask)) ) {
                $maybe_need_union = 1;
            }
        }
        $alertGroup = new PiAlertGroup();
        $alertGroup->piSystemName = $alert->piSystemName;
        $alertGroup->fromSystem = $alert->fromSystem??'';
        $alertGroup->toSystem = $alert->toSystem??'';
        $alertGroup->channel = $alert->channel??'';
        $alertGroup->interface = $alert->interface??'';
        $alertGroup->errText = $alert->errText;
        $mask = TextAnalysisUtil::replaceMessageIdToMask($alert->errText);
        $mask = TextAnalysisUtil::replacePortNumberToMask($mask);
        $alertGroup->errTextMask = $mask;
        $alertGroup->firstAlert = $alert->timestamp;
        $alertGroup->lastAlert = $alert->timestamp;
        $alertGroup->maybe_need_union = $maybe_need_union;
        $alertGroup->saveToDatabase();
        return $alertGroup;
    }

    /**
     * Получить список групп алертов, с которыми потенциально можно объединить данную группу
     * @param PiAlertGroup $alertGroup
     * @return array $mask => $altAlertGroup
     */
    public static function getMaybeSimilarAlertGroup(PiAlertGroup $alertGroup) : array {
        $result = array();
        $query = DB::prepare("SELECT *  FROM alert_group WHERE piSystemName=? AND fromSystem=? AND toSystem=? AND (channel=? OR interface=?) AND group_id != ?");
        $query->execute(array($alertGroup->piSystemName, $alertGroup->fromSystem, $alertGroup->toSystem, $alertGroup->channel, $alertGroup->interface, $alertGroup->group_id));
        while($row = $query->fetch()) {
            $alertGroup2 = new PiAlertGroup($row);
            $mask = TextAnalysisUtil::getMaskFromTexts($alertGroup->errTextMask, $alertGroup2->errTextMask);
            if ( !is_null($mask) ) {
                $result[ $mask ] = $alertGroup2;
            }
        }
        return $result;
    }

    /**
     * Переносит привязку алертов из первой группы алертов во вторую
     * @param PiAlertGroup $alertGroupFrom Группа под удаление
     * @param PiAlertGroup $alertGroupTarget
     * @return bool
     */
    public static function unionAlertGroups(PiAlertGroup $alertGroupFrom, PiAlertGroup $alertGroupTarget) : bool
    {
        if (!is_null($alertGroupFrom->comment)) {
            if (is_null($alertGroupTarget->comment)) {
                $alertGroupTarget->comment = $alertGroupFrom->comment;
            } elseif ($alertGroupTarget->comment != $alertGroupFrom->comment) {
                $alertGroupTarget->comment = $alertGroupFrom->comment.PHP_EOL.PHP_EOL.'Старый коммент: '.$alertGroupTarget->comment;
            }
        }
        if (is_null($alertGroupTarget->user_id)) {
            if (is_null($alertGroupFrom->user_id)) {
                if (is_null($alertGroupTarget->last_user_id)) {
                    $alertGroupTarget->last_user_id = $alertGroupFrom->last_user_id;
                }
            } else {
                $alertGroupTarget->setUserId($alertGroupFrom->user_id);
            }
        }

        $unionMask = TextAnalysisUtil::getMaskFromTexts($alertGroupFrom->errTextMask, $alertGroupTarget->errTextMask);
        if (is_null($unionMask)) {
            return false;
        }
        $alertGroupTarget->errTextMask = $unionMask;
        if ($alertGroupTarget->status == PiAlertGroup::CLOSE) {
            $alertGroupTarget->status = PiAlertGroup::REOPEN;
        }
        if ($alertGroupFrom->interface != $alertGroupTarget->interface) {
            $alertGroupTarget->multi_interface = 1;
        } else {
            $alertGroupTarget->multi_interface = max($alertGroupTarget->multi_interface, $alertGroupFrom->multi_interface);
        }
        $alertGroupTarget->firstAlert = min($alertGroupTarget->firstAlert, $alertGroupFrom->firstAlert);
        $alertGroupTarget->lastAlert = max($alertGroupTarget->lastAlert, $alertGroupFrom->lastAlert);
        $alertGroupTarget->lastUserAction = max($alertGroupTarget->lastUserAction, $alertGroupFrom->lastUserAction);

        $query = DB::prepare("UPDATE alerts SET group_id = ? WHERE group_id = ?");
        $query->execute(array($alertGroupTarget->group_id, $alertGroupFrom->group_id));

        $result = $alertGroupFrom->deleteAlertGroup();
        if ( $result ) {
            $alertGroupTarget->saveToDatabase();
            return true;
        }
        return false;
    }

}