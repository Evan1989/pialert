SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

DELETE FROM `settings` WHERE code = 'LINK TO SUPPORT RULES';

CREATE TABLE `user_systems` (
    `user_id` int NOT NULL,
    `system_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user_systems`
    ADD PRIMARY KEY (`user_id`,`system_name`);

ALTER TABLE `user_systems`
    ADD CONSTRAINT `user_systems_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

UPDATE `settings` SET `value` = '3.0' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;