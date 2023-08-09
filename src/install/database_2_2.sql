SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Структура таблицы `messages_stat`
--

CREATE TABLE `messages_stat` (
                                 `id` INT(11) NOT NULL AUTO_INCREMENT,
                                 `piSystemName` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                                 `fromSystem` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                                 `toSystem` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                                 `interface` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                                 `timestamp` DATETIME NULL DEFAULT NULL,
                                 `messageСount` INT(11) NULL DEFAULT NULL,
                                 `messageProcTime` INT(11) NULL DEFAULT NULL,
                                 `messageProcTimePI` INT(11) NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`) USING BTREE,
                                 INDEX `timestamp` (`timestamp`) USING BTREE,
                                 INDEX `piSystemName` (`piSystemName`) USING BTREE,
                                 INDEX `interface` (`interface`) USING BTREE,
                                 INDEX `fromSystem` (`fromSystem`) USING BTREE,
                                 INDEX `toSystem` (`toSystem`) USING BTREE
)
    COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=1
;


--
-- Дамп данных таблицы `message_stat`
--

INSERT INTO `messages_stat` (`piSystemName`, `fromSystem`, `toSystem`, `interface`,`timestamp`, `messageCount`, `messageProcTime`, `messageProcTimePI`) VALUES
('af.pip.pip-db1','SystemA_P', 'SystemB_P', 'Interface_Sync_Out','2022-05-01 01:00:00', 1,3,2);

-- --------------------------------------------------------

INSERT INTO `settings` (`grp`, `code`, `value`, `type`) VALUES
(null, 'JOB MESSAGE STATISTIC REFRESH TIME', '', 'datetime'),
(null, 'JOB MESSAGE STATISTIC DELETE REFRESH TIME', '', 'datetime'),
(null, 'MESSAGE STATISTIC STORE DAYS', '365', 'number'),
(null, 'MESSAGE STATISTIC SERVICE USER', 'user', 'text'),
(null, 'MESSAGE STATISTIC SERVICE PASSWORD', 'password', 'password'),
('SYSTEMS', 'SYSTEMS SETTINGS', '{\"af.pfq.pfq-db\":{ \r\n\"SID\":\"PFQ\",\r\n\"Host\":\"pfq-wd.komus.net:8000\",\r\n\"StatEnable\":\"true\"\r\n}}', 'textarea');



UPDATE `settings` SET `value` = '2.2' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;