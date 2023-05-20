SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

ALTER TABLE `alert_group` CHANGE `errText` `errText` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `alert_group` CHANGE `errTextMask` `errTextMask` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `alert_group` CHANGE `errTextMainPart` `errTextMainPart` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `alerts` CHANGE `errText` `errText` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

UPDATE `settings` SET `value` = '2.0' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;