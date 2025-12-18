<?php
include 'header.php';
include 'db.php';

// Fetch books
$books = $conn->query("SELECT * FROM books WHERE quantity > 0");

// Fetch members
$members = $conn->query("SELECT * FROM members");
?>

<h2>Issue a Book</h2>

<!-- Notification div -->
<div id="notification" style="display:none; padding:10px; margin-bottom:15px;"></div>

<form id="issueForm">
    <label>Select Book:</label><br>
    <select name="book_id" required>
        <?php while ($b = $books->fetch_assoc()) { ?>
            <option value="<?= $b['id'] ?>">
                <?= $b['title'] ?> (<?= $b['quantity'] ?> available)
            </option>
        <?php } ?>
    </select><br><br>

    <label>Select Member:</label><br>
    <select name="member_id" required>
        <?php while ($m = $members->fetch_assoc()) { ?>
            <option value="<?= $m['id'] ?>">
                <?= $m['name'] ?> - <?= $m['department'] ?>
            </option>
        <?php } ?>
    </select><br><br>

    <button type="submit">Issue Book</button>
</form>

<style>
#notification.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
#notification.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
// AJAX form submission
document.getElementById('issueForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent page reload

    let formData = new FormData(this);

    fetch('issue_book_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        let notif = document.getElementById('notification');
        notif.innerHTML = data.includes("successfully") ? "Book issued successfully!" : data;
        notif.className = data.includes("successfully") ? 'success' : 'error';
        notif.style.display = 'block';

        // Hide after 3 seconds
        setTimeout(() => { notif.style.display = 'none'; }, 3000);

        // Reset form
        if(data.includes("successfully")) this.reset();
    })
    .catch(err => console.error(err));
});
</script>

<?php include 'footer.php'; ?>
