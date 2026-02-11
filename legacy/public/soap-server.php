<?php
declare(strict_types=1);

require_once __DIR__ . '/LegacyLibraryService.php';

$cfg = require __DIR__ . '/config.php';
$wsdl = $cfg['wsdl_path'];

ini_set('soap.wsdl_cache_enabled', '0');

$server = new SoapServer($wsdl, [
  'cache_wsdl' => WSDL_CACHE_NONE,
  'uri' => $cfg['service_namespace'],
]);

$server->setClass(LegacyLibraryService::class);

try {
  $server->handle();
} catch (Throwable $e) {
    // Всегда возвращаем SOAP Fault (ошибку SOAP)
  $server->fault('Server', $e->getMessage());
}
