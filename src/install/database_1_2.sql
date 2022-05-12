SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

UPDATE `pages` SET `page_icon` = 'display' WHERE `pages`.`menu_id` = 1;

INSERT INTO `pages` (`menu_id`, `number`, `group_icon`, `group_caption`, `page_icon`, `page_caption`, `url`) VALUES
(9, '53', NULL, NULL, 'documents', 'menuMassAlerts', '/src/pages/mass_alerts.php');

INSERT INTO `user_rights` (`user_id`, `menu_id`) VALUES
(1, 9);

UPDATE `settings` SET `value` = '1.2' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;