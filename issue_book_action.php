<?php
include 'db.php';

$book_id = $_POST['book_id'] ?? null;
$member_id = $_POST['member_id'] ?? null;
$issue_date = date('Y-m-d');

// Basic validation
if (!$book_id || !$member_id) {
    echo "Please select both a book and a member.";
    exit;
}

// Check if book has quantity
$bookCheck = $conn->query("SELECT quantity FROM books WHERE id = $book_id")->fetch_assoc();
if (!$bookCheck) {
    echo "Book not found!";
    exit;
} elseif ($bookCheck['quantity'] <= 0) {
    echo "This book is out of stock!";
    exit;
}

// Insert into issued_books
$stmt = $conn->prepare("INSERT INTO issued_books (book_id, member_id, issue_date) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $book_id, $member_id, $issue_date);

if ($stmt->execute()) {
    // Reduce book quantity
    $conn->query("UPDATE books SET quantity = quantity - 1 WHERE id = $book_id");
    echo "Book issued successfully!";
} else {
    echo "Error: " . $stmt->error;
}
?>
