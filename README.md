# ✅ PiAlert

PiAlert is system for automating the work of SAP PI/PO support team via aggregation of alerts (CBMA messages).

![Purpose of the system](https://raw.githubusercontent.com/Evan1989/pialert/main/img/goal.jpg "Purpose of the system")

Language support:
* English
* Русский

# 💻 Server requirements for PiAlert
1. 1 CPU, 1 GB HDD, 1 GB RAM
2. PHP 8.0
3. PHP dependencies via composer.json:
   1. extension  = curl
   2. extension  = mbstring
   3. extension  = pdo_mysql
4. MySQL/MariaDB
5. Nginx

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
8. _(optional)_ For the semi-automatic PiAlert upgrade system to work, need permissions to write into / (under php user)

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
8. _(необязательно)_ Для работы полуавтоматического обновления PiAlert, нужны права на запись в / (под пользователем php)

# 🚧 Requirements in SAP PI/PO
1. ⚠️Network access from SAP PI/PO to PiAlert (HTTP/HTTPS)
2. Alert Rule to generate alerts (CBMA): add all Communication Components and choose name of new Consumer.
3. Create ICO to send json messages generated in CBMA as configured from above:
   1. JMS Sender (read queue for created Consumer) → Without mappings → REST Receiver
   2. End-point /src/api/cbma_alert_input.php
4. _(optional)_ Create ICO to check network problems between PiAlert and SAP PI/PO:
   1. REST polling (every 5 minutes) → Dynamic Receiver Determination (ignore option) → Any Receiver (never called)
   2. End-point /src/api/network_check.php?system=_SAP_PI_system_name_
   4. If there is no call, then PiAlert will notify the support team on Dashboard page.