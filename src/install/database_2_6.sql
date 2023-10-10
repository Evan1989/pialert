SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

INSERT INTO `settings` (`grp`, `code`, `value`, `type`) VALUES
(null, 'JOB MESSAGE STATISTIC ALERT REFRESH TIME', '', 'datetime'),
('SYSTEMS', 'ALERT MESSAGE COUNT', '10', 'number'),
('SYSTEMS', 'ALERT MESSAGE PROCESSING TIME', '10', 'number');

ALTER TABLE `messages_stat`
    ADD INDEX `messageProcTime` (`messageProcTime`),
    ADD INDEX `messageCount` (`messageCount`);

UPDATE `settings` SET `value` = '{\"af.pip.pip-db\":{ \r\n\"SID\":\"PIP\",\r\n\"host\":\"pip-db.host.net:8000\",\r\n\"statEnable\":true\r\n}}' WHERE `settings`.`value` = 'SYSTEMS SETTINGS';

UPDATE `settings` SET `value` = '2.6' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;