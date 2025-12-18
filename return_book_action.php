<?php
include 'db.php';

$issue_id = $_POST['issue_id'];
$return_date = date('Y-m-d');

// Get book_id first
$getBook = $conn->query("SELECT book_id FROM issued_books WHERE id = $issue_id");
$row = $getBook->fetch_assoc();
$book_id = $row['book_id'];

// Update issued_books
$conn->query("
    UPDATE issued_books 
    SET return_date = '$return_date' 
    WHERE id = $issue_id
");

// Increase book quantity
$conn->query("
    UPDATE books 
    SET quantity = quantity + 1 
    WHERE id = $book_id
");

echo "✅ Book returned successfully!";
