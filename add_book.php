<?php
include 'header.php';
include 'db.php';

// If form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $author = $_POST["author"];
    $quantity = $_POST["quantity"];

    $sql = "INSERT INTO books (title, author, quantity) VALUES ('$title', '$author', '$quantity')";

    if ($conn->query($sql)) {
        echo "<p style='color: green;'>Book added successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
    }
}
?>

<h2>Add a New Book</h2>

<form method="POST">
    <label>Book Title:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Author:</label><br>
    <input type="text" name="author" required><br><br>

    <label>Quantity:</label><br>
    <input type="number" name="quantity" required><br><br>

    <button type="submit">Add Book</button>
</form>

<?php include 'footer.php'; ?>
