SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` ADD `multi_interface` INT DEFAULT '0' AFTER `interface`;

UPDATE alert_group as g SET g.multi_interface = 1 WHERE (SELECT count(DISTINCT a.interface) FROM alerts a WHERE a.group_id=g.group_id)>=2;

UPDATE `settings` SET `value` = '1.7' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;