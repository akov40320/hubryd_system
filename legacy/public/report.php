<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/xml.php';

$type = $_GET['type'] ?? 'overdue';
$raw = isset($_GET['raw']) && $_GET['raw'] === '1';

if ($type !== 'overdue') {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Unknown report type";
  exit;
}

$pdo = db();

/**
 * For demo we consider "overdue" = loan older than 7 days and not returned.
 */
$rows = $pdo->query("
  SELECT
    b.inventory_number,
    b.title,
    b.author,
    l.reader_card,
    l.date_taken
  FROM physical_loans l
  JOIN physical_books b ON b.id = l.book_id
  WHERE l.date_returned IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

$today = new DateTimeImmutable('now');

$xml = xml_header() . '<OverdueReport><generated_at>' . $today->format(DATE_ATOM) . '</generated_at><items>';

foreach ($rows as $r) {
  $taken = DateTimeImmutable::createFromFormat('Y-m-d', $r['date_taken']) ?: $today;
  $days = (int)$taken->diff($today)->format('%a');
  if ($days <= 7) continue;

  $xml .= '<item>'
    . '<inventory_number>' . xml_escape($r['inventory_number']) . '</inventory_number>'
    . '<title>' . xml_escape($r['title']) . '</title>'
    . '<author>' . xml_escape($r['author']) . '</author>'
    . '<reader_card>' . xml_escape($r['reader_card']) . '</reader_card>'
    . '<date_taken>' . xml_escape($r['date_taken']) . '</date_taken>'
    . '<days_overdue>' . $days . '</days_overdue>'
    . '</item>';
}

$xml .= '</items></OverdueReport>';

if ($raw) {
  header('Content-Type: application/xml; charset=utf-8');
  echo $xml;
  exit;
}

$dom = new DOMDocument();
$dom->loadXML($xml);

$xsl = new DOMDocument();
$xsl->load(__DIR__ . '/report.xsl');

$proc = new XSLTProcessor();
$proc->importStylesheet($xsl);

header('Content-Type: text/html; charset=utf-8');
echo $proc->transformToXML($dom);
