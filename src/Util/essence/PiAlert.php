<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\DB;

class PiAlert {

    public int $group_id;

    public ?string $alertRuleId;
    public string $piSystemName = '';
    public ?string $priority = null;
    public string $timestamp;

    public ?string $messageId = null;

    public ?string $fromSystem = null;
    public ?string $toSystem = null;

    public ?string $adapterType = null;
    public ?string $channel = null;

    public ?string $ICOName = null;
    public ?string $interface = null;
    public ?string $namespace = null;

    public ?string $monitoringUrl = null;

    public ?string $errCategory = null;
    public ?string $errCode = null;
    public string $errText;

    public ?string $UDSAttributes = null;

    public function __construct(string|array $inputData) {
        if ( is_array($inputData) ) {
            $this->createFromRow($inputData);
        } else {
            $this->createFromJson($inputData);
        }
    }

    private function createFromJson(string $jsonFromPi) : void {
        $alert = json_decode($jsonFromPi, null, 10);
        if ( is_null($alert) ) {
            return;
        }

        $this->alertRuleId = $alert->RuleId;
        $this->piSystemName = $alert->Component;
        $this->priority = $alert->Severity??null;
        $this->timestamp = date("Y-m-d H:i:s", strtotime($alert->Timestamp));

        $this->messageId = $alert->MsgId??null;

        $this->fromSystem = $this->toSystemName($alert->FromParty??null, $alert->FromService??null);
        $this->toSystem = $this->toSystemName($alert->ToParty??null, $alert->ToService??null);

        $this->adapterType = $alert->AdapterType??null;
        $this->channel = $alert->Channel??null;

        $this->ICOName = $alert->ScenarioName??null;
        $this->interface = $alert->Interface??null;
        $this->namespace = $alert->Namespace??null;

        $this->monitoringUrl = $alert->MonitoringUrl??null;

        $this->errCategory = $alert->ErrCat??null;
        $this->errCode = $alert->ErrCode??null;
        $this->errText = $alert->ErrText??'...empty error text...';

        if ( isset($alert->UDSAttrs) ) {
            if ( is_string($alert->UDSAttrs) ) {
                $this->UDSAttributes = $alert->UDSAttrs;
            } else {
                $this->UDSAttributes = json_encode($alert->UDSAttrs);
            }
        } else {
            $this->UDSAttributes = null;
        }
    }

    private function createFromRow(array $row ) : void {
        $this->group_id = $row['group_id'];
        $this->alertRuleId = $row['alertRuleId'];
        $this->piSystemName = $row['piSystemName'];
        $this->priority = $row['priority'];
        $this->timestamp = $row['timestamp'];
        $this->messageId = $row['messageId'];
        $this->fromSystem = $row['fromSystem'];
        $this->toSystem = $row['toSystem'];
        $this->adapterType = $row['adapterType'];
        $this->channel = $row['channel'];
        $this->interface = $row['interface'];
        $this->namespace = $row['namespace'];
        $this->monitoringUrl = $row['monitoringUrl'];
        $this->errCategory = $row['errCategory'];
        $this->errCode = $row['errCode'];
        $this->errText = $row['errText'];
        $this->UDSAttributes = $row['UDSAttributes'];
    }

    /**
     * Либо интерфейс, либо канал
     * @return string
     */
    public function getObject(): string {
        if ($this->interface) {
            return $this->interface;
        }
        return $this->channel??'';
    }

    public function getHTMLErrorText() :string {
        $text = htmlspecialchars($this->errText);
        $text = replaceLinksWithATag($text);
        return nl2br($text);
    }

    public function getHTMLMessageId(): ?string {
        if ( $this->monitoringUrl ) {
            return "<a target='_blank' href='".$this->monitoringUrl."'>".$this->messageId."</a>";
        }
        return $this->messageId;
    }

    private function toSystemName(?string $party, ?string $system) : ?string {
        if ( empty($party) ) {
            return $system;
        }
        return $party.'|'.$system;
    }

    public function saveNewToDatabase() : bool {
        $query = DB::prepare("INSERT INTO alerts (group_id, alertRuleId, piSystemName, priority, timestamp, messageId, fromSystem, toSystem, adapterType, channel, interface, namespace, monitoringUrl, errCategory, errCode, errText, UDSAttributes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $query->execute(array( $this->group_id, $this->alertRuleId, $this->piSystemName, $this->priority, $this->timestamp, $this->messageId, $this->fromSystem, $this->toSystem, $this->adapterType, $this->channel, $this->interface, $this->namespace, $this->monitoringUrl, $this->errCategory, $this->errCode, $this->errText, $this->UDSAttributes ));
    }
}