SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Структура таблицы `bs_systems`
--

CREATE TABLE `bs_systems` (
                              `code` VARCHAR(50) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_unicode_ci',
                              `name` VARCHAR(100) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_unicode_ci',
                              `contact` VARCHAR(1000) NOT NULL DEFAULT '\'\'' COLLATE 'utf8mb4_unicode_ci',
                              `comment` VARCHAR(200) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                              INDEX `code` (`code`) USING BTREE
)
    COMMENT='Таблица для храннения информации о системах: SLD код, название системы, контакты поддержки\r\n'
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;

--
-- Дамп данных таблицы `bs_systems`
--

INSERT INTO `bs_systems` (`code`, `name`, `contact`,`comment`) VALUES ('BS_System_P', 'Система', 'example_contact@komus.net','');


INSERT INTO `pages` (`number`, `group_icon`, `group_caption`, `page_icon`, `page_caption`, `url`) VALUES
(50, 'gear', 'menuGroupSettings', 'bookmark', 'menuSystems', '/src/pages/systems.php');

INSERT INTO `user_rights` (`user_id`, `menu_id`) VALUES
(1, 10);

UPDATE `settings` SET `value` = '2.4' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;