SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Структура таблицы `systems`
--

CREATE TABLE `systems` (
                           `id` INT(11) NOT NULL AUTO_INCREMENT,
                           `code` VARCHAR(50) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_unicode_ci',
                           `name` VARCHAR(100) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_unicode_ci',
                           `contact` VARCHAR(200) NOT NULL DEFAULT '\'\'' COLLATE 'utf8mb4_unicode_ci',
                           `comment` VARCHAR(200) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                           PRIMARY KEY (`id`) USING BTREE,
                           INDEX `code` (`code`) USING BTREE,
                           INDEX `name` (`name`) USING BTREE,
                           INDEX `contact` (`contact`) USING BTREE
)
    COMMENT='Таблица для храннения информации о системах: SLD код, название системы, контакты поддержки\r\n'
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;

--
-- Дамп данных таблицы `systems`
--

INSERT INTO `systems` (`code`, `name`, `contact`,`comment`) VALUES ('BS_System_P', 'Система', 'example_contact@komus.net','');


INSERT INTO `pages` (`number`, `group_icon`, `group_caption`, `page_icon`, `page_caption`, `url`) VALUES
(50, NULL, NULL, 'bookmark', 'menuSystems', '/src/pages/systems.php');

INSERT INTO `user_rights` (`user_id`, `menu_id`) VALUES
(1, 10);

UPDATE `settings` SET `value` = '2.4' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;