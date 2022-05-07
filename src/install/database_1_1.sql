SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Структура таблицы `user_statistic_online`
--

CREATE TABLE `user_statistic_online` (
    `id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `date` date DEFAULT NULL,
    `seconds` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `user_statistic_online`
--
ALTER TABLE `user_statistic_online`
    ADD PRIMARY KEY (`id`),
    ADD KEY `user_id` (`user_id`,`date`) USING BTREE;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `user_statistic_online`
--
ALTER TABLE `user_statistic_online`
    ADD CONSTRAINT `user_statistic_online_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

UPDATE `settings` SET `value` = '1.1' WHERE `settings`.`code` = 'DATABASE VERSION';

COMMIT;