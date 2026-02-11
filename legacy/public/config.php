<?php
declare(strict_types=1);

return [
  // SQLite file for the legacy module.
  // It will be created on first init (see init_db.php).
  'db_path' => __DIR__ . '/../data/library.sqlite',

  // WSDL definition for the SOAP service
  'wsdl_path' => __DIR__ . '/library.wsdl',

  // SOAP service namespace (must match WSDL)
  'service_namespace' => 'urn:LegacyLibraryService',
];
