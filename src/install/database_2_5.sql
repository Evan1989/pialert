SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `bs_systems`
    ADD PRIMARY KEY (`code`);

UPDATE `settings` SET `value` = '2.5' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;