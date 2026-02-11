<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/xml.php';

final class LegacyLibraryService {

  /**
   * Returns XML string with one book or error.
   * WSDL: string getBookByInventory(string $inventory_number)
   */
  public function getBookByInventory(string $inventory_number): string {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT * FROM physical_books WHERE inventory_number = :inv LIMIT 1");
    $stmt->execute([':inv' => $inventory_number]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
      return xml_header() . '<BookInfo><success>false</success><message>Книга не найдена</message></BookInfo>';
    }

    return xml_header() . '<BookInfo>'
      . '<success>true</success>'
      . '<book>'
        . '<inventory_number>' . xml_escape($book['inventory_number']) . '</inventory_number>'
        . '<title>' . xml_escape($book['title']) . '</title>'
        . '<author>' . xml_escape($book['author']) . '</author>'
        . '<year>' . (int)$book['year'] . '</year>'
        . '<location>' . xml_escape($book['location']) . '</location>'
        . '<status>' . xml_escape($book['status']) . '</status>'
      . '</book>'
    . '</BookInfo>';
  }

  /**
   * Returns XML string with list of books.
   * WSDL: string searchBooksByAuthor(string $author_name)
   */
  public function searchBooksByAuthor(string $author_name): string {
    $pdo = db();

    $stmt = $pdo->prepare("
      SELECT * FROM physical_books
      WHERE author LIKE :q
      ORDER BY author, title
      LIMIT 200
    ");
    $stmt->execute([':q' => '%' . $author_name . '%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $xml = xml_header() . '<BookList>'
      . '<success>true</success>'
      . '<query>' . xml_escape($author_name) . '</query>'
      . '<count>' . count($rows) . '</count>'
      . '<books>';

    foreach ($rows as $b) {
      $xml .= '<book>'
        . '<inventory_number>' . xml_escape($b['inventory_number']) . '</inventory_number>'
        . '<title>' . xml_escape($b['title']) . '</title>'
        . '<author>' . xml_escape($b['author']) . '</author>'
        . '<year>' . (int)$b['year'] . '</year>'
        . '<location>' . xml_escape($b['location']) . '</location>'
        . '<status>' . xml_escape($b['status']) . '</status>'
      . '</book>';
    }

    $xml .= '</books></BookList>';
    return $xml;
  }

  /**
   * registerLoan(inventory_number, reader_card) -> LoanResult
   */
  public function registerLoan(string $inventory_number, string $reader_card): array {
    $reader_card = trim($reader_card);
    if ($reader_card === '' || strlen($reader_card) < 3) {
      return [
        'success' => false,
        'message' => 'Недействительный номер читательского билета',
        'loan_id' => null,
        'inventory_number' => $inventory_number,
        'date_taken' => null,
      ];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("SELECT * FROM physical_books WHERE inventory_number=:inv LIMIT 1");
      $stmt->execute([':inv'=>$inventory_number]);
      $book = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$book) {
        $pdo->rollBack();
        return [
          'success' => false,
          'message' => 'Книга не найдена',
          'loan_id' => null,
          'inventory_number' => $inventory_number,
          'date_taken' => null,
        ];
      }

      if ($book['status'] !== 'available') {
        $pdo->rollBack();
        return [
          'success' => false,
          'message' => 'Книга уже выдана или недоступна',
          'loan_id' => null,
          'inventory_number' => $inventory_number,
          'date_taken' => null,
        ];
      }

      $dateTaken = (new DateTimeImmutable('now'))->format('Y-m-d');

      $pdo->prepare("INSERT INTO physical_loans(book_id, reader_card, date_taken, date_returned) VALUES (?,?,?,NULL)")
          ->execute([(int)$book['id'], $reader_card, $dateTaken]);

      $loanId = (int)$pdo->lastInsertId();

      $pdo->prepare("UPDATE physical_books SET status='borrowed' WHERE id=?")->execute([(int)$book['id']]);

      $pdo->commit();

      return [
        'success' => true,
        'message' => "Книга успешно выдана читателю {$reader_card}",
        'loan_id' => $loanId,
        'inventory_number' => $inventory_number,
        'date_taken' => $dateTaken,
      ];
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      return [
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage(),
        'loan_id' => null,
        'inventory_number' => $inventory_number,
        'date_taken' => null,
      ];
    }
  }

  /**
   * returnBook(inventory_number) -> ReturnResult
   */
  public function returnBook(string $inventory_number): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("SELECT * FROM physical_books WHERE inventory_number=:inv LIMIT 1");
      $stmt->execute([':inv'=>$inventory_number]);
      $book = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$book) {
        $pdo->rollBack();
        return [
          'success' => false,
          'message' => 'Книга не найдена',
          'inventory_number' => $inventory_number,
          'date_returned' => null,
        ];
      }

      if ($book['status'] !== 'borrowed') {
        $pdo->rollBack();
        return [
          'success' => false,
          'message' => 'Книга не находится в статусе "borrowed"',
          'inventory_number' => $inventory_number,
          'date_returned' => null,
        ];
      }

      $loanStmt = $pdo->prepare("
        SELECT id FROM physical_loans
        WHERE book_id = :book_id AND date_returned IS NULL
        ORDER BY id DESC LIMIT 1
      ");
      $loanStmt->execute([':book_id' => (int)$book['id']]);
      $loanId = $loanStmt->fetchColumn();

      if (!$loanId) {
        $pdo->rollBack();
        return [
          'success' => false,
          'message' => 'Активная выдача не найдена',
          'inventory_number' => $inventory_number,
          'date_returned' => null,
        ];
      }

      $dateReturned = (new DateTimeImmutable('now'))->format('Y-m-d');
      $pdo->prepare("UPDATE physical_loans SET date_returned=? WHERE id=?")->execute([$dateReturned,(int)$loanId]);
      $pdo->prepare("UPDATE physical_books SET status='available' WHERE id=?")->execute([(int)$book['id']]);

      $pdo->commit();

      return [
        'success' => true,
        'message' => 'Книга возвращена',
        'inventory_number' => $inventory_number,
        'date_returned' => $dateReturned,
      ];
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      return [
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage(),
        'inventory_number' => $inventory_number,
        'date_returned' => null,
      ];
    }
  }
}
