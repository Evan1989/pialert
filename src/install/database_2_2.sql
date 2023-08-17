SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alerts` CHANGE `priority` `priority` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `alerts` CHANGE `adapterType` `adapterType` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

UPDATE `settings` SET `value` = '2.2' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;