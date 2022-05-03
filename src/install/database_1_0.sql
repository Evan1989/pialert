SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;


--
-- База данных: `alert_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `alertRuleId` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `piSystemName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL,
  `messageId` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fromSystem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toSystem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adapterType` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interface` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `namespace` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monitoringUrl` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `errCategory` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `errCode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `errText` varchar(3000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UDSAttributes` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `alerts`
--

INSERT INTO `alerts` (`id`, `group_id`, `alertRuleId`, `piSystemName`, `priority`, `timestamp`, `messageId`, `fromSystem`, `toSystem`, `adapterType`, `channel`, `interface`, `namespace`, `monitoringUrl`, `errCategory`, `errCode`, `errText`, `UDSAttributes`) VALUES
(1, 1, '67824a2f4d5630eb4acb5920a474d6f6', 'af.pip.pip-db1', 'HIGH', '2022-05-01 23:48:26', 'c3dd3cc7-9d8e-11ec-c37c-00000074edca', 'SystemA_P', 'SystemB_P', 'XI_J2EE_MESSAGING_SY', NULL, 'Interface_Sync_Out', 'http://namespace/example', 'https://pip.example.com:8000/webdynpro/resources/sap.com/tc~lm~itsam~ui~mainframe~wd/FloorPlanApp?applicationID=com.sap.itsam.mon.xi.msg&msgid=c3dd3cc7-9d8e-11ec-c37c-00000074edca', 'XI_J2EE_ADAPTER_ENGINE', 'DELIVERY_ERROR', 'ERROR_SENDING_HTTP_REQUEST-Message Processing Failed. Reason : com.sap.httpclient.exception.ProtocolException: Data is not repeatable.', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `alert_group`
--

CREATE TABLE `alert_group` (
  `group_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `comment` varchar(2000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `piSystemName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fromSystem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toSystem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interface` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `errText` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `errTextMask` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_alert` datetime DEFAULT NULL,
  `last_alert` datetime DEFAULT NULL,
  `last_user_action` datetime DEFAULT NULL,
  `maybe_need_union` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `alert_group`
--

INSERT INTO `alert_group` (`group_id`, `status`, `comment`, `user_id`, `piSystemName`, `fromSystem`, `toSystem`, `channel`, `interface`, `errText`, `errTextMask`, `first_alert`, `last_alert`, `last_user_action`, `maybe_need_union`) VALUES
(1, 1, NULL, NULL, 'af.pip.pip-db1', 'SystemA_P', 'SystemB_P', '', 'Interface_Sync_Out', 'ERROR_SENDING_HTTP_REQUEST-Message Processing Failed. Reason : com.sap.httpclient.exception.ProtocolException: Data is not repeatable.', 'ERROR_SENDING_HTTP_REQUEST-Message Processing Failed. Reason : com.sap.httpclient.exception.ProtocolException: Data is not repeatable.', '2022-05-01 23:48:26', '2022-05-01 23:48:26', NULL, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `pages`
--

CREATE TABLE `pages` (
  `menu_id` int(11) NOT NULL,
  `number` int(11) NOT NULL DEFAULT '0',
  `group_icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_caption` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_caption` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `pages`
--

INSERT INTO `pages` (`menu_id`, `number`, `group_icon`, `group_caption`, `page_icon`, `page_caption`, `url`) VALUES
(1, 50, NULL, NULL, 'bar-chart', 'menuDashboard', '/src/pages/dashboard.php'),
(2, 90, 'gear', 'menuGroupSettings', 'lock', 'menuRights', '/src/pages/rights.php'),
(3, 55, NULL, NULL, 'envelope', 'menuAlerts', '/src/pages/alerts.php'),
(4, 85, 'gear', 'menuGroupSettings', 'people', 'menuUsers', '/src/pages/users.php'),
(5, 70, 'cloud', 'menuGroupAnalytics', 'graph-up', 'menuStatistics', '/src/pages/statistics.php'),
(6, 100, 'gear', 'menuGroupSettings', 'wrench', 'menuSettings', '/src/pages/settings.php'),
(7, 75, 'cloud', 'menuGroupAnalytics', 'download', 'menuReports', '/src/pages/export.php');

-- --------------------------------------------------------

--
-- Структура таблицы `page_blocks`
--

CREATE TABLE `page_blocks` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `element_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `grp` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `grp`, `code`, `value`, `type`) VALUES
(1, 'OTHER', 'CBMA SERVICE PASSWORD', '2wsx%TGB', 'password'),
(2, 'COMPANY', 'COMPANY NAME', 'Test installation', 'text'),
(3, 'COMPANY', 'LINK TO SUPPORT RULES', '', 'url'),
(4, 'SYSTEMS', 'SYSTEMS NAMES', '{\"af.pip.pip-db1\":\"PIP\",\r\n\"af.pop.pop-db1\":\"POP\"}', 'textarea'),
(5, NULL, 'DATABASE VERSION', '1.0', 'number'),
(6, NULL, 'SYSTEMS NETWORK CHECK', '{}', 'textarea'),
(7, 'SYSTEMS', 'AVERAGE ALERT INTERVAL RATIO', '0', 'number');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salt` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `FIO` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `online` timestamp NULL DEFAULT NULL,
  `blocked` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `salt`, `FIO`, `language`, `online`, `blocked`) VALUES
(1, 'admin@company.address', 'e8aa5dcd53b5b3103f107725b55722cfaa7254a2', 'z7ncUhkt7h', 'Test user', 'en', '2022-03-31 16:16:57', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_rights`
--

CREATE TABLE `user_rights` (
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_rights`
--

INSERT INTO `user_rights` (`user_id`, `menu_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7);

-- --------------------------------------------------------

--
-- Структура таблицы `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(20) DEFAULT NULL,
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usetime` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `cache` (
    `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `content` longtext COLLATE utf8mb4_unicode_ci,
    `expiry_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--


--
-- Индексы таблицы `cache`
--
ALTER TABLE `cache`
    ADD PRIMARY KEY (`name`) USING BTREE;

--
-- Индексы таблицы `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `piSystemName` (`piSystemName`);

--
-- Индексы таблицы `alert_group`
--
ALTER TABLE `alert_group`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_alert` (`last_alert`),
  ADD KEY `piSystemName` (`piSystemName`,`fromSystem`,`toSystem`,`channel`,`interface`);

--
-- Индексы таблицы `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`menu_id`);

--
-- Индексы таблицы `page_blocks`
--
ALTER TABLE `page_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`menu_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code` (`code`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Индексы таблицы `user_rights`
--
ALTER TABLE `user_rights`
  ADD PRIMARY KEY (`user_id`,`menu_id`),
  ADD KEY `user_rights_ibfk_1` (`menu_id`);

--
-- Индексы таблицы `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=294;

--
-- AUTO_INCREMENT для таблицы `alert_group`
--
ALTER TABLE `alert_group`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT для таблицы `pages`
--
ALTER TABLE `pages`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `page_blocks`
--
ALTER TABLE `page_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=258;

--
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `alert_group` (`group_id`) ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `alert_group`
--
ALTER TABLE `alert_group`
  ADD CONSTRAINT `alert_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `page_blocks`
--
ALTER TABLE `page_blocks`
  ADD CONSTRAINT `page_blocks_ibfk_3` FOREIGN KEY (`menu_id`) REFERENCES `pages` (`menu_id`),
  ADD CONSTRAINT `page_blocks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ограничения внешнего ключа таблицы `user_rights`
--
ALTER TABLE `user_rights`
  ADD CONSTRAINT `user_rights_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `pages` (`menu_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_rights_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;