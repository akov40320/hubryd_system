Запуск проекта (Legacy PHP + Node.js gateway + Frontend)

1) Требования
- PHP 8.x
  - включены расширения: soap, sqlite3, dom, xsl
- Node.js 18+ (лучше 20 LTS)
- npm

2) Запуск Legacy (PHP + SOAP + SQLite + отчёт XML/XSLT)
Откройте терминал в папке проекта:

    cd legacy/public
    php -S 127.0.0.1:8080

В другом терминале инициализируйте базу (создаст SQLite и заполнит тестовыми данными):

    cd legacy/public
    php init_db.php

Проверка (должно открываться в браузере):
- SOAP endpoint: http://127.0.0.1:8080/soap-server.php
- WSDL: http://127.0.0.1:8080/soap-server.php?wsdl
- Legacy SOAP client: http://127.0.0.1:8080/admin.php
- Отчёт (HTML через XSLT): http://127.0.0.1:8080/report.php?type=overdue
- Отчёт (сырой XML): http://127.0.0.1:8080/report.php?type=overdue&raw=1

3) Запуск Modern (Node.js gateway + REST API + статический фронт)
Откройте третий терминал:

    cd gateway
    npm install
    npm run seed
    npm start

После запуска:
- Web UI: http://127.0.0.1:3000
- REST API: http://127.0.0.1:3001

4) Порядок запуска
1) Сначала запустите Legacy на 127.0.0.1:8080
2) Потом запускайте Node gateway (он при старте проверяет доступность SOAP)

5) Если порты отличаются
Если  запускаете legacy не на 127.0.0.1:8080, обновите настройки gateway (env/конфиг) на свой адрес legacy/WSDL и перезапустите Node.
