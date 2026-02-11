<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS physical_books (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  inventory_number TEXT UNIQUE NOT NULL,
  title TEXT NOT NULL,
  author TEXT NOT NULL,
  year INTEGER NOT NULL,
  location TEXT NOT NULL,
  status TEXT NOT NULL CHECK(status IN ('available','borrowed','lost'))
);

CREATE TABLE IF NOT EXISTS physical_loans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  book_id INTEGER NOT NULL,
  reader_card TEXT NOT NULL,
  date_taken TEXT NOT NULL,
  date_returned TEXT NULL,
  FOREIGN KEY(book_id) REFERENCES physical_books(id)
);
");

# Seed if empty
$count = (int)$pdo->query("SELECT COUNT(*) FROM physical_books")->fetchColumn();
if ($count > 0) {
  echo "DB already seeded: {$count} books\n";
  exit(0);
}

$books = [
  ["LIB-2024-001","Мастер и Маргарита","Булгаков Михаил Афанасьевич",1966,"Секция А, стеллаж 5, полка 3","available"],
  ["LIB-2024-002","Clean Code","Robert C. Martin",2008,"Зал иностранной литературы, стеллаж 12","available"],
  ["LIB-2024-003","Преступление и наказание","Достоевский Фёдор Михайлович",1866,"Секция А, стеллаж 2, полка 1","available"],
  ["LIB-2024-004","Война и мир","Толстой Лев Николаевич",1869,"Секция А, стеллаж 1, полка 1","available"],
  ["LIB-2024-005","The Pragmatic Programmer","Andrew Hunt; David Thomas",1999,"Зал иностранной литературы, стеллаж 11","available"],
  ["LIB-2024-006","Design Patterns","Erich Gamma; Richard Helm; Ralph Johnson; John Vlissides",1994,"Зал иностранной литературы, стеллаж 10","available"],
  ["LIB-2024-007","Гарри Поттер и философский камень","Джоан Роулинг",1997,"Секция B, стеллаж 3, полка 2","available"],
  ["LIB-2024-008","Sapiens","Yuval Noah Harari",2011,"Зал научпопа, стеллаж 4, полка 1","available"],
  ["LIB-2024-009","1984","George Orwell",1949,"Зал иностранной литературы, стеллаж 2","available"],
  ["LIB-2024-010","Brave New World","Aldous Huxley",1932,"Зал иностранной литературы, стеллаж 2","available"],
  ["LIB-2024-011","Алгоритмы. Построение и анализ","Томас Кормен",2009,"Секция IT, стеллаж 1, полка 1","available"],
  ["LIB-2024-012","JavaScript: The Good Parts","Douglas Crockford",2008,"Секция IT, стеллаж 2, полка 1","available"],
  ["LIB-2024-013","You Don't Know JS","Kyle Simpson",2015,"Секция IT, стеллаж 2, полка 2","available"],
  ["LIB-2024-014","Refactoring","Martin Fowler",1999,"Секция IT, стеллаж 3, полка 1","available"],
  ["LIB-2024-015","The Clean Coder","Robert C. Martin",2011,"Секция IT, стеллаж 3, полка 2","available"],
  ["LIB-2024-016","Dune","Frank Herbert",1965,"Секция C, стеллаж 1, полка 2","available"],
  ["LIB-2024-017","Fahrenheit 451","Ray Bradbury",1953,"Секция C, стеллаж 2, полка 1","available"],
  ["LIB-2024-018","To Kill a Mockingbird","Harper Lee",1960,"Секция C, стеллаж 2, полка 2","available"],
  ["LIB-2024-019","The Little Prince","Antoine de Saint-Exupéry",1943,"Секция детской литературы, стеллаж 1","available"],
  ["LIB-2024-020","Введение в базы данных","Кристофер Дж. Дейт",2003,"Секция IT, стеллаж 4, полка 1","available"],
];

$stmt = $pdo->prepare("
  INSERT INTO physical_books (inventory_number, title, author, year, location, status)
  VALUES (:inv,:title,:author,:year,:location,:status)
");

foreach ($books as $b) {
  [$inv,$title,$author,$year,$location,$status] = $b;
  $stmt->execute([
    ':inv'=>$inv, ':title'=>$title, ':author'=>$author, ':year'=>$year,
    ':location'=>$location, ':status'=>$status
  ]);
}

# Create a couple of overdue loans (date_taken older, not returned)
$bookId1 = (int)$pdo->query("SELECT id FROM physical_books WHERE inventory_number='LIB-2024-001'")->fetchColumn();
$bookId2 = (int)$pdo->query("SELECT id FROM physical_books WHERE inventory_number='LIB-2024-009'")->fetchColumn();
$pdo->prepare("INSERT INTO physical_loans(book_id, reader_card, date_taken, date_returned) VALUES (?,?,?,NULL)")
    ->execute([$bookId1,'R-12345','2024-03-10']);
$pdo->prepare("INSERT INTO physical_loans(book_id, reader_card, date_taken, date_returned) VALUES (?,?,?,NULL)")
    ->execute([$bookId2,'STUDENT-2024-001','2024-03-05']);

$pdo->exec("UPDATE physical_books SET status='borrowed' WHERE id IN ($bookId1,$bookId2)");

echo "Seeded DB with 20 books + 2 active loans\n";
