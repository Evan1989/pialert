SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

INSERT INTO `settings` (`grp`, `code`, `value`, `type`) VALUES
(null, 'JOB MESSAGE STATISTIC ALERT REFRESH TIME', '', 'datetime'),
('SYSTEMS', 'ALERT MESSAGE COUNT', '10', 'number'),
('SYSTEMS', 'ALERT MESSAGE PROCESSING TIME', '10', 'number');

UPDATE `settings` SET `value` = '2.6' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;