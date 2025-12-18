<?php
include 'header.php';
include 'db.php';

$sql = "
SELECT 
    books.title AS book_title,
    members.name AS member_name,
    members.department,
    issued_books.issue_date,
    issued_books.return_date
FROM issued_books
JOIN books ON issued_books.book_id = books.id
JOIN members ON issued_books.member_id = members.id
ORDER BY issued_books.issue_date DESC
";

$result = $conn->query($sql);
?>

<h2>Books Issue / Return Log</h2>

<table>
    <tr>
        <th>Book</th>
        <th>Member</th>
        <th>Department</th>
        <th>Issue Date</th>
        <th>Return Date</th>
        <th>Status</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['book_title'] ?></td>
            <td><?= $row['member_name'] ?></td>
            <td><?= $row['department'] ?></td>
            <td><?= $row['issue_date'] ?></td>
            <td><?= $row['return_date'] ?? '-' ?></td>
            <td>
                <?php
                if ($row['return_date'] == NULL) {
                    echo "<span class='error'>Issued</span>";
                } else {
                    echo "<span class='success'>Returned</span>";
                }
                ?>
            </td>
        </tr>
    <?php } ?>
</table>

<?php include 'footer.php'; ?>
