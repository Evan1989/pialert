SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Структура таблицы `bs_systems`
--

CREATE TABLE `bs_systems` (
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Таблица для хранения информации о системах: SLD код, название системы, контакты поддержки\r\n';

--
-- Дамп данных таблицы `bs_systems`
--

INSERT INTO `bs_systems` (`code`, `name`, `contact`,`comment`) VALUES ('BS_System_P', 'Система', 'example_contact@komus.net','');


INSERT INTO `pages` (`number`, `group_icon`, `group_caption`, `page_icon`, `page_caption`, `url`) VALUES
(82, 'gear', 'menuGroupSettings', 'bookmark', 'menuSystems', '/src/pages/systems.php');

INSERT INTO `user_rights` (`user_id`, `menu_id`) VALUES
(1, 10);

UPDATE `settings` SET `value` = '2.4' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;