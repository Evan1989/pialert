SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

UPDATE `pages` SET `page_icon` = 'person-workspace' WHERE `pages`.`menu_id` = 1;
UPDATE `pages` SET `page_icon` = 'envelope-exclamation' WHERE `pages`.`menu_id` = 3;
UPDATE `pages` SET `page_icon` = 'calendar-week' WHERE `pages`.`menu_id` = 8;
UPDATE `pages` SET `page_icon` = 'eraser-fill' WHERE `pages`.`menu_id` = 9;

UPDATE `settings` SET `value` = '1.3' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;