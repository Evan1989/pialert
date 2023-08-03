SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Структура таблицы `messages_stat`
--

CREATE TABLE `messages_stat`(
                                `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `piSystemName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                                `fromSystem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `toSystem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `interface` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `timestamp` datetime NOT NULL,
                                `message_count` int(11) DEFAULT NULL,
                                `messageProcTime` int(11) DEFAULT NULL,
                                `messageProcTimePI` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=UTF8MB4_UNICODE_CI;


--
-- Дамп данных таблицы `message_stat`
--

INSERT INTO `messages_stat` (`piSystemName`, `fromSystem`, `toSystem`, `interface`,`timestamp`, `message_count`, `messageProcTime`, `messageProcTimePI`) VALUES
('af.pip.pip-db1','SystemA_P', 'SystemB_P', 'Interface_Sync_Out','2022-05-01 01:00:00', 1,3,2);

-- --------------------------------------------------------

INSERT INTO `settings` (`grp`, `code`, `value`, `type`) VALUES
(null, 'JOB MESSAGESTAT REFRESH TIME', '', 'datetime'),
(null, 'JOB MESSAGESTATDELETE REFRESH TIME', '', 'datetime'),
(null, 'MESSAGESTAT STORE DAYS', '365', 'number'),
('SYSTEMS', 'SYSTEMS SETTINGS', '{\"af.pfq.pfq-db\":{ \r\n\"SID\":\"PFQ\",\r\n\"Host\":\"pfq-wd.komus.net:8000\",\r\n\"StatEnable\":\"true\",\r\n\"user\":\"user\",\r\n\"password\":\"password!\"\r\n}}', 'textarea');



UPDATE `settings` SET `value` = '2.2' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;