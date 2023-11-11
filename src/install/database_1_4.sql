SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` ADD `last_user_id` INT NULL AFTER `user_id`, ADD INDEX (`last_user_id`);
ALTER TABLE `alert_group` ADD `comment_datetime` DATETIME NULL AFTER `comment`;
# noinspection SqlWithoutWhere
UPDATE alert_group SET comment_datetime = last_user_action;
UPDATE alert_group SET last_user_id=user_id, user_id=null WHERE status = 4;

ALTER TABLE `alert_group` ADD FOREIGN KEY (`last_user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

UPDATE `settings` SET `value` = '1.4' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;