SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

INSERT INTO `settings` (`id`, `grp`, `code`, `value`, `type`) VALUES
(8, NULL, 'JOB CACHE REFRESH TIME', NULL, 'datetime');

UPDATE `settings` SET `value` = '2.1' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;