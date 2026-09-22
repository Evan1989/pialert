SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` ADD `comment_ai` varchar(2000) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `comment_datetime`;

ALTER TABLE `alert_group`
    ADD INDEX `comment_ai` (`comment_ai`(700));

UPDATE `settings` SET `value` = '4.5' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;
