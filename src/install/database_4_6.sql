SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` CHANGE `comment_ai` `comment_ai` varchar(10000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

UPDATE `settings` SET `value` = '4.6' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;