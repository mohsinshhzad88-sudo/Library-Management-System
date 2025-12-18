<?php include 'header.php'; include 'db.php'; ?>

<h2>Add Member</h2>

<form method="POST">
    <label>Name</label>
    <input type="text" name="name" required>

    <label>Department</label>
    <input type="text" name="department" required>

    <button type="submit">Add Member</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $dept = $_POST['department'];

    $stmt = $conn->prepare("INSERT INTO members (name, department) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $dept);

    if ($stmt->execute()) {
        echo "<p class='success'>Member added successfully</p>";
    } else {
        echo "<p class='error'>Error adding member</p>";
    }
}
?>

<?php include 'footer.php'; ?>
