<?php
declare(strict_types=1);

return [
    // Файл SQLite для легаси-модуля.
    // Будет создан при первой инициализации (см. init_db.php).
  'db_path' => __DIR__ . '/../data/library.sqlite',

    // WSDL-описание SOAP-сервиса
  'wsdl_path' => __DIR__ . '/library.wsdl',

    // Пространство имён SOAP-сервиса (должно совпадать с WSDL)
  'service_namespace' => 'urn:LegacyLibraryService',
];
