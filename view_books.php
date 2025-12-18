<?php
include 'header.php';
include 'db.php';

$sql = "SELECT * FROM books";
$result = $conn->query($sql);
?>

<h2>All Books</h2>

<table border="5" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Author</th>
        <th>Quantity</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['title'] ?></td>
            <td><?= $row['author'] ?></td>
            <td><?= $row['quantity'] ?></td>
        </tr>
    <?php } ?>
</table>

<?php include 'footer.php'; ?>