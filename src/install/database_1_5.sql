SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` ADD `errTextMask` VARCHAR(3000) NULL AFTER `errTextMask`;

ALTER TABLE `alert_group` ADD INDEX( errTextMainPart(190) );

UPDATE `settings` SET `value` = '1.5' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;