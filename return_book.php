<?php
include 'header.php';
include 'db.php';

// Fetch issued books that are NOT returned
$sql = "
SELECT 
    issued_books.id AS issue_id,
    books.title,
    members.name
FROM issued_books
JOIN books ON issued_books.book_id = books.id
JOIN members ON issued_books.member_id = members.id
WHERE issued_books.return_date IS NULL
";

$result = $conn->query($sql);
?>

<h2>Return Book</h2>

<!-- Notification -->
<div id="notification" style="display:none;"></div>

<form id="returnForm">
    <label>Select Issued Book:</label><br>

    <select name="issue_id" required>
        <option value="">-- Select --</option>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <option value="<?= $row['issue_id'] ?>">
                <?= $row['title'] ?> — <?= $row['name'] ?>
            </option>
        <?php } ?>
    </select><br><br>

    <button type="submit">Return Book</button>
</form>

<script>
document.getElementById('returnForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch('return_book_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        let notif = document.getElementById('notification');
        notif.innerHTML = data;
        notif.style.display = 'block';
        notif.style.background = '#d4edda';
        notif.style.color = '#155724';
        notif.style.padding = '10px';
        notif.style.marginBottom = '15px';

        // Reload page after 1.5 sec so dropdown updates
        setTimeout(() => {
            location.reload();
        }, 1500);
    });
});
</script>

<?php include 'footer.php'; ?>
