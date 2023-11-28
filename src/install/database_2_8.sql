SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alerts` ADD INDEX(`fromSystem`);
ALTER TABLE `alerts` ADD INDEX(`toSystem`);


UPDATE `settings` SET `value` = '2.8' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;