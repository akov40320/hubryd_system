<?php
declare(strict_types=1);

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $cfg = require __DIR__ . '/config.php';

  $dbFile = $cfg['db_path'];
  $dir = dirname($dbFile);
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }

  $pdo = new PDO('sqlite:' . $dbFile);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec('PRAGMA foreign_keys = ON;');

  return $pdo;
}
