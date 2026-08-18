SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE alerts
    DROP INDEX `piSystemName`,
    ADD INDEX `piSystemName` (`piSystemName`, `timestamp`);

UPDATE `settings` SET `value` = '4.3' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;