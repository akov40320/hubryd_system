<?php
declare(strict_types=1);

/**
 * Small "admin" SOAP client to demonstrate legacy-to-legacy integration.
 * Accessible in browser: http://localhost:8080/admin.php
 */

$cfg = require __DIR__ . '/config.php';
ini_set('soap.wsdl_cache_enabled', '0');

$wsdlUrl = (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/library.wsdl' : $cfg['wsdl_path']);

$client = new SoapClient($wsdlUrl, [
  'cache_wsdl' => WSDL_CACHE_NONE,
  'trace' => true,
]);

$inventory = $_GET['inventory'] ?? 'LIB-2024-001';
$author = $_GET['author'] ?? 'Martin';

$resultBook = $client->getBookByInventory($inventory);
$resultAuthor = $client->searchBooksByAuthor($author);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <title>Legacy Admin (SOAP client)</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 24px; }
    code, pre { background: #f4f4f4; padding: 8px; border-radius: 8px; display: block; overflow-x: auto; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .card { border: 1px solid #ddd; border-radius: 12px; padding: 16px; }
    input { padding: 8px; border-radius: 8px; border: 1px solid #ccc; width: 280px; }
    button { padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; cursor: pointer; }
  </style>
</head>
<body>
  <h1>Legacy Admin</h1>
  <p>Это “легаси”-админка: PHP SOAP-клиент вызывает PHP SOAP-сервер.</p>

  <form method="get">
    <div>
      <label>Инвентарный номер:</label>
      <input name="inventory" value="<?= htmlspecialchars($inventory, ENT_QUOTES) ?>" />
      <label>Автор:</label>
      <input name="author" value="<?= htmlspecialchars($author, ENT_QUOTES) ?>" />
      <button type="submit">Обновить</button>
    </div>
  </form>

  <div class="grid" style="margin-top:16px;">
    <div class="card">
      <h2>getBookByInventory()</h2>
      <pre><?= htmlspecialchars($resultBook) ?></pre>
    </div>
    <div class="card">
      <h2>searchBooksByAuthor()</h2>
      <pre><?= htmlspecialchars($resultAuthor) ?></pre>
    </div>
  </div>

  <h2 style="margin-top:24px;">SOAP Request/Response (последний вызов)</h2>
  <div class="grid">
    <div class="card">
      <h3>Last Request</h3>
      <pre><?= htmlspecialchars($client->__getLastRequest()) ?></pre>
    </div>
    <div class="card">
      <h3>Last Response</h3>
      <pre><?= htmlspecialchars($client->__getLastResponse()) ?></pre>
    </div>
  </div>
</body>
</html>
