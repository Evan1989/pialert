SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE alerts
    DROP INDEX `group_id`,
    ADD INDEX `group_id` (`group_id`, `timestamp`);

UPDATE `settings` SET `value` = '4.2' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;