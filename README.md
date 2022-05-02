# ✅ PiAlert codebase

PiAlert is system for automating the work of SAP PI/PO support team via aggregation of alerts (CBMA messages).
Language Support:
* English
* Русский

![Purpose of the system](https://raw.githubusercontent.com/Evan1989/pialert/main/img/goal.jpg "Purpose of the system")

___

# 💻 Server requirements for PiAlert
1. 1 CPU, 1 GB HDD, 1 GB RAM
2. PHP 8.0
3. PHP via composer.json
4. MySQL/MariaDB
5. Nginx

___

# 👷 Installation steps (EN)
1. Download PiAlert
2. Install Development Dependencies `composer install`
3. Go through the browser to http://host/ and follow the installer steps
4. Log in via browser to /src/pages/ as the start user:
   1. Login: admin@company.address
   2. Password: welc0m3
5. Enter system settings into /src/pages/settings.php
6. Get the required users in /src/pages/users.php
7. Grant new users rights via /src/pages/rights.php

# 👷 Installation steps (RU)
1. Скачать систему PiAlert
2. Скачать необходимые библиотеки через `composer install`
3. Зайти через браузер на http://host/ и выполнить шаги установщика
4. Зайти через браузер на /src/pages/ под стартовым пользователем:
   1. Логин: admin@company.address
   2. Пароль: welc0m3
5. Вбить параметры системы в /src/pages/settings.php
6. Завести необходимых пользователей в /src/pages/users.php
7. Предоставить новым пользователям права через /src/pages/rights.php

___

# Requirements for SAP PI/PO
1. Network access from SAP PI/PO to PiAlert (HTTP/HTTPS)
2. Alert rule to generate alerts (CBMA) for all systems for the selected consumer.
3. First ICO to send json messages generated in CBMA as configured from above:
   1. JMS -> REST
   2. Without mappings
   3. Target End-point /src/api/cbma_alert_input.php
4. Second ICO, which will be every 5 minutes call address /src/api/network_check.php?system=<SAP PI system name>. If there is no call, then PiAlert will notify the support team on Dashboard page) about network problems.