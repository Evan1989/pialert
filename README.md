# ✅ PiAlert

PiAlert is system for automating the work of SAP PI/PO support team via aggregation of alerts (CBMA messages).

![Purpose of the system](https://raw.githubusercontent.com/Evan1989/pialert/main/img/goal.jpg "Purpose of the system")

Language support:
* English
* Русский

# ⚒️ For LLM
1. MCP server in /src/api/mcp.php, more info in /src/api/mcp.md

# 💻 Server requirements for PiAlert
1. 1 CPU, 1 GB HDD, 1 GB RAM
2. PHP 8.4
3. PHP dependencies via composer.json:
   1. extension  = curl
   2. extension  = mbstring
   3. extension  = pdo_mysql
   4. extension  = simplexml
4. Composer
5. MySQL/MariaDB
6. Nginx

# 👷 Installation steps (EN)
1. Download PiAlert
2. Install Development Dependencies `composer install`
3. Go through the browser to http://host/ and follow the installer steps
4. Log in to PiAlert with start user:
   1. Login: admin@company.address
   2. Password: welc0m3
5. Enter system settings into /src/pages/settings.php
6. Create the required users in /src/pages/users.php
7. Grant new users rights via /src/pages/rights.php
8. _(optional)_ Network connection from PiAlert server to the Internet (for semi-automatic updates and GeoIP)
9. _(optional)_ For PiAlert semi-automatic update to work, you need write permissions to / (under the php user)
10. _(optional)_ Add script /src/main_job.php to cron settings every 5 minutes (it is recommended to add error log redirection to php log, for example, 2>>/var/log/php-fpm/error.log)

# 👷 Installation steps (RU)
1. Скачать систему PiAlert
2. Скачать необходимые библиотеки через `composer install`
3. Зайти через браузер на http://host/ и выполнить шаги установщика
4. Зайти в PiAlert под стартовым пользователем:
   1. Логин: admin@company.address
   2. Пароль: welc0m3
5. Вбить параметры системы в /src/pages/settings.php
6. Завести необходимых пользователей в /src/pages/users.php
7. Предоставить новым пользователям права через /src/pages/rights.php
8. _(необязательно)_ Сетевое соединение от сервера PiAlert в интернет (для полуавтоматического обновления и GeoIP)
9. _(необязательно)_ Для работы полуавтоматического обновления PiAlert, нужны права на запись в / (под пользователем php)
10. _(необязательно)_ Добавить скрипт /src/main_job.php в настройки cron раз в 5 минут (рекомендуется добавить перенаправление лога ошибок в лог php, например, 2>>/var/log/php-fpm/error.log)

# 🚧 Requirements in SAP PI/PO
1. ⚠️Network access from SAP PI/PO to PiAlert (HTTP/HTTPS)
2. Alert Rule to generate alerts (CBMA): add all Communication Components and choose name of new Consumer.
3. Create ICO to send json messages generated in CBMA as configured from above:
   1. JMS Sender (read queue for created Consumer) → Without mappings → REST Receiver
   2. End-point /src/api/cbma_alert_input.php
4. _(optional)_ Create ICO to check network problems between PiAlert and SAP PI/PO:
   1. REST polling (every 5 minutes) → Dynamic Receiver Determination (ignore option) → Any Receiver (never called)
   2. End-point /src/api/network_check.php?system=_SAP_PI_system_name_
   3. If there is no call, then PiAlert will notify the support team on Dashboard page.
5. _(optional)_ Network access from PiAlert to SAP PI/PO (HTTP) and user in SAP PI/PO with role SAP_XI_MONITOR_J2EE for statistic monitoring.