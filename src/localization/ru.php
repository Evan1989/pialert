<?php

use EvanPiAlert\Util\Text;

$texts = array(

    'change' => 'Поменять',
    'pageGenerated' => 'Страница сгенерирована',
    'surnameName' => 'ФИО',
    'status' => 'Статус',
    'actions' => 'Действия',
    'user' => 'Пользователь',
    'employee' => 'Сотрудник',
    'responsibleEmployee' => 'Ответственный сотрудник',
    'addUser' => 'Добавить пользователя',
    'save' => 'Сохранить',
    'summary' => 'Итого',
    'pieces' => 'шт.',
    'perDay' => 'в сутки',
    'alertCount' => 'Алертов',
    'today' => 'Сегодня',
    'name' => 'Название',
    'search' => 'Поиск',
    'date' => 'Дата',
    'sender' => 'Отправитель',
    'receiver' => 'Получатель',
    'object' => 'Объект',
    'error' => 'Ошибка',
    'comment' => 'Комментарий',
    'last' => 'Последний',
    'reloadPage' => 'Обновить страницу',
    'complete' => 'Готово',
    'back' => 'Назад',

    'statusNew' => 'Новое',
    'statusIgnore' => 'Игнор',
    'statusManual' => 'Ручные шаги',
    'statusWait' => 'На исправлении',
    'statusClose' => 'Закрыто',
    'statusReopen' => 'Переоткрыто',

    'dayNameArray' => array(1 => 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'),
    'yearsArray' => array('год', 'года', 'лет'),
    'monthsArray' => array('месяц', 'месяца', 'месяцев'),
    'daysArray' => array('день', 'дня', 'дней'),
    'hoursArray' => array('час', 'часа', 'часов'),
    'minutesArray' => array('минута', 'минуты', 'минут'),
    'secondsArray' => array('секунда', 'секунды', 'секунд'),
    'immediately' => 'меньше секунды',

    'menuGroupSettings' => 'Настройки',
    'menuGroupAnalytics' => 'Аналитика',
    'menuDashboard' => 'Рабочий стол',
    'menuAlerts' => 'Алерты',
    'menuRights' => 'Права доступа',
    'menuUsers' => 'Пользователи',
    'menuStatistics' => 'Статистика',
    'menuSettings' => 'Параметры',
    'menuReports' => 'Отчеты',
    'menuRules' => 'Регламент поддержки',
    'menuProfile' => 'Профиль',

    'usersPageHeader' => 'Все пользователи',
    'usersAddUserSuccess' => 'Для пользователя '.Text::REPLACE_PATTERN.' создана ссылка для входа: '.Text::REPLACE_PATTERN,
    'usersLastActionTime' => 'Заходил '.Text::REPLACE_PATTERN.' назад',

    'profilePageHeader' => 'Мой пользователь',
    'profileLanguage' => 'Язык',
    'profileAlertCount' => 'Назначено ошибок',
    'profileChangePassword' => 'Поменять пароль',
    'profileNewPassword' => 'Новый пароль',
    'profilePasswordChangeSuccess' => 'Пароль обновлен',
    'profilePasswordChangeFail' => 'Не удалось обновить пароль!',

    'settingsUpdateSuccess' => 'Настройки обновлены.',
    'settingsUpdateFail' => 'Не удалось сохранить одну из настроек!',
    'settingsInstallButton' => 'Установщик',
    'settingsCommonSettings' => 'Общие параметры',
    'settingsHostname' => 'Хост системы',
    'settingsCodeVersion' => 'Версия кода',
    'settingsDataBaseVersion' => 'Версия базы данных',
    'settingsGroupCompany' => 'Данные компании',
    'settingsGroupSystem' => 'Данные о системах',
    'settingsGroupOther' => 'Прочие параметры',

    'statisticAllSystems' => 'Все системы',
    'statisticAlertGroupCount' => 'Типов ошибок',
    'statisticAlert24HourCount' => 'Алертов за 24 часа',
    'statisticAlertTodayChart' => 'График алертов за сегодня',
    'statisticAlertWeekCount' => 'Алертов за неделю',
    'statisticAlertMonthCount' => 'Алертов за месяц',
    'statisticAlertMonthChart' => 'График алертов за месяц',
    'statisticAlertTotalCount' => 'Алертов за всю историю',

    'chartsNormalForDay' => 'Норма для '.Text::REPLACE_PATTERN,
    'chartsAverageDay' => 'Средний календарный день',

    'exportPageHeader' => 'Выгрузка данных из PiAlert',
    'exportLoadOnSystem' => 'Нагрузка на систему при скачивании',
    'exportMainAlertGroupBase' => 'База знаний о всех типах ошибок',
    'exportLow' => 'Низкая',
    'exportErrorOnGenerateReport' => 'Ошибка при генерации документа',

    'alertsPageHeader' => 'Последние 1000 алертов',

    'dashboardPageHeader' => 'Агрегированные алерты из SAP PI',
    'dashboardCommentPlaceholder' => 'Шаги, которые уже выполнены, либо который надо выполнить при повторении алерта',
    'dashboardCheckAlertGroupAsCompleteButton' => 'По группе пришел новый алерт. Кликните, если необходимые действия выполнены',
    'dashboardCheckAlertGroupAsCompleteFail' => 'Необходимо назначить ответственного или сменить статус.',
    'dashboardShowOldAlerts' => 'Показывать алерты старше двух недель',
    'dashboardNoConnectToPiSystem' => 'Давно нет вызовов от систем, проверьте логи в SAP PI',
    'dashboardSupportOnline' => 'В системе online несколько сотрудников',
    'dashboardTitleToTopBillCounter' => 'Всего актуальных алертов на странице',
    'dashboardRequisites' => 'Реквизиты',
    'dashboardMaskOrError' => 'Текст ошибки/шаблона',
    'dashboardMaskOrErrorTitle' => 'Шаблон, это обобщение похожих текстов ошибок. * (звездочка) = 100 любых символов',
    'dashboardMaskAfterUnion' => 'Шаблона текста ошибки в случае объединения',
    'dashboardFirstAlert' => 'Первый алерт',
    'dashboardLastAlert' => 'Последний алерт',
    'dashboardShowAlertButton' => 'Сами алерты',
    'dashboardShowStatisticButton' => 'Статистика по группе',
    'dashboardUnionGroupButton' => 'Группу можно объединить с другой',
    'dashboardUnionGroupButtonStep2' => 'слить все алерты из группы ошибок в этой строке с',
    'dashboardThisGroup' => 'данной группой',
    'dashboardUnionSuccess' => 'Группы ошибок успешно объединены.',
    'dashboardUnionFail' => 'Не удалось объединить данные группы ошибок.',
    'dashboardUnionFatalError' => 'Невозможно объединить данные группы ошибок.',
    'dashboardLegend' => 'Легенда',
    'dashboardLegendActualAlert' => 'актуальный алерт: он относительно свежий (пришел в нерабочее время или сегодня) и по нему ещё не было совершено действий',
    'dashboardLegendNew' => 'обнаружена новая группа ошибок',
    'dashboardLegendIgnore' => 'при следующем повторении этого типа ошибок, делать ничего не нужно',
    'dashboardLegendManual' => 'на подобную ошибку необходимо выполнение ручных шагов сотрудником интеграции',
    'dashboardLegendWait' => 'ждем исправления ошибки в рамках задачи, указанной в комментариях',
    'dashboardLegendClose' => 'проблема устранена',
    'dashboardLegendReopen' => 'тип ошибок был исправлен, но пришел новый алерт',

    'authorizationPageTitle' => 'Вход в систему PiAlert',
    'authorizationPageHeader' => 'Необходимо пройти авторизацию',
    'authorization403Error' => 'Данная страница недоступна для вашего пользователя, обратитесь к администратору сервера.',

    'installPageHeader' => 'Установка и обновление PiAlert',
    'installNextStep' => 'Следующий шаг',
    'installError' => 'Произошла ошибка: '.Text::REPLACE_PATTERN,
    'installStep1Header' => 'Установка системы. Шаг 1',
    'installStep1Body' => 'Необходимо создать конфигурационный файл /src/<b>config.php</b>, в котором заполнить ключевые поля: хост системы и параметры доступа к базе данных. Базу надо создать заранее, пустой.
        <br><br>
        Пример файла:',
    'installDatabaseUpdateError' => 'Скрипт для обновления базы данных в папке /src/install/ на версию '.Text::REPLACE_PATTERN.' не найден.',
    'installStep2Header' => 'Установка системы. Шаг 2',
    'installStep2Body' => 'Необходимо создать таблицы в Базе данных. Это может занять какое-то время.',
    'installStep2Success' => 'База данных успешно создана',
    'installUpdateHeader' => 'Обновление системы',
    'installUpdateBody' => 'Версия PHP кода системы: '.Text::REPLACE_PATTERN.'
        <br>
        Необходимо обновить версию структур базы данных до того же уровня.',
    'installUpdateSuccess' => 'Таблицы базы данных успешно обновлены до версии '.Text::REPLACE_PATTERN,
    'installFinish' => 'Система корректно развернута',
    'installTrySystem' => 'Попробовать PiAlert',

    'apiCBMAServiceInfo' => "<h1>Сервис для приема алертов (CBMA) из SAP PI</h1>
        <p>
            Доступ осуществляется через HTTP Basic Auth по адресу ".Text::REPLACE_PATTERN."
        </p>
        <p>
            Данный сервис предназначен для получения алертов (CBMA) из SAP PI и их обработки.
            Ожидается, что данные в него попадут через сценарий JMS → Passthrogh → REST.
        </p>
        <p>
            На вход сервис ожидает POST запрос с json. Формат сообщения - стандартный, генерируемый через AlertRule в SAP PI 7.4/7.5:
        </p>
        <pre>".
        '{
    "ToParty": "",
    "ScenarioId": "dir://ICO/c49519aec1213061b74c7e3c04e5ee64",
    "RuleId": "67824a2f4d5630ebb4cb5920a474d6f6",
    "AdapterType": "XI_J2EE_MESSAGING_SYSTEM",
    "ErrCat": "Mapping",
    "ToService": "SystemB_P",
    "ScenarioName": "|SystemA_P|Interface_Sync_Out||",
    "Severity": "HIGH",
    "Timestamp": "2022-03-14T17:36:02Z",
    "MonitoringUrl": "https://pip.example.com:8000/webdynpro/resources/sap.com/tc~lm~itsam~ui~mainframe~wd/FloorPlanApp?applicationID=com.sap.itsam.mon.xi.msg&msgid=770a5a44-a39b-11ec-8f7e-000000cacaa6",
    "MsgId": "770a5a44-a39b-11ec-8f7e-000000cacaa6",
    "FromService": "SystemA_P",
    "Namespace": "http://namespace/example",
    "ErrCode": "RESOURCE_EXCEPTION",
    "ErrLabel": "1220",
    "ErrText": "Could not determine mapping steps for message 770a5a44-a39b-11ec-8f7e-000000cacaa6",
    "FromParty": "",
    "Component": "af.pip.pip-db",
    "Interface": "Interface_Sync_Out"
}'.
        "</pre>
         <p>
            В ответ приходит пустой HTTP Body и HTTP код 200 либо 500.
        </p>",

    'apiNetworkCheckServiceInfo' => "<h1>Сервис для свидетельства канарейки</h1>
        <p>
            Доступ осуществляется через HTTP Basic Auth по адресу ".Text::REPLACE_PATTERN."
        </p>
        <p>
            В SAP PI настраивается сценарий REST polling → Dynamic Reciever Determination (всегда выдающий false + галочка ignore в ICO) → Any,
            который раз в 5 минут вызывает данный сервис. Если вызова не было, то PiAlert предположит проблемы
            с сетью между SAP PI и PiAlert и должит об этом сотруднику поддержки в разделе Рабочий стол.
        </p>
        <p>
            На вход сервис ожидает GET запрос с одним параметром:
        </p>
        <ul>
            <li>system - строка, в которой указано название систем, аналогично тому как генерируется в поле Component из алерта (CBMA) SAP PI</li>
        </ul>
        <p>
            В ответ приходит HTTP 200 и пустой json.
        </p>",

);