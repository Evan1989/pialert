SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` ADD `alert_link` VARCHAR(2000) NULL AFTER `maybe_need_union`;

ALTER TABLE `messages_stat`
    MODIFY `messageProcTime` BIGINT NULL,
    MODIFY `messageProcTimePI` BIGINT NULL;


UPDATE `settings` SET `value` = '2.7' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;