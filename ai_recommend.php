<?php
/**
 * ai_recommend.php
 * AI Book Recommendation Page for Library Management System
 *
 * Approach: content-based filtering using book genre as the signal.
 * - Looks at which books a member has issued before, and the genre
 *   of those books.
 * - Recommends other in-stock books sharing that genre.
 * - If the member is new / has no history, falls back to the most
 *   frequently issued books (popularity-based cold start).
 *
 * Matches your existing schema:
 *   books(id, title, author, quantity)
 *   members(id, name, department)
 *   issued_books(book_id, member_id, issue_date)
 *
 * This file auto-adds a `genre` column to `books` the first time it
 * runs, since your current schema doesn't have one yet.
 */

include 'header.php';
include 'db.php';

// ---------------------------------------------------------------
// AUTO-SETUP: add genre column to books if it doesn't exist yet
// ---------------------------------------------------------------
$check = $conn->query("SHOW COLUMNS FROM books LIKE 'genre'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE books ADD COLUMN genre VARCHAR(50) DEFAULT 'General'");
}

// ---------------------------------------------------------------
// CORE AI LOGIC
// ---------------------------------------------------------------
function get_ai_recommendations($conn, $member_id, $limit = 5) {

    // 1. Genres this member has issued before, with frequency
    $genreCounts = [];
    $stmt = $conn->prepare(
        "SELECT b.genre, COUNT(*) as cnt
         FROM issued_books i
         JOIN books b ON b.id = i.book_id
         WHERE i.member_id = ?
         GROUP BY b.genre"
    );
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $genreCounts[$row['genre']] = (int)$row['cnt'];
    }
    $stmt->close();

    // 2. Books already issued by this member (exclude from suggestions)
    $excludeIds = [];
    $stmt = $conn->prepare("SELECT DISTINCT book_id FROM issued_books WHERE member_id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $excludeIds[] = (int)$row['book_id'];
    }
    $stmt->close();

    // 3. COLD START: no history -> most popular in-stock books
    if (empty($genreCounts)) {
        $sql = "SELECT b.*,
                       (SELECT COUNT(*) FROM issued_books ib WHERE ib.book_id = b.id) as total_issues
                FROM books b
                WHERE b.quantity > 0
                ORDER BY total_issues DESC, b.title ASC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $recs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($recs as &$r) { $r['reason'] = "Popular pick"; }
        return $recs;
    }

    // 4. Candidate pool: in-stock books not already issued to this member
    $sql = "SELECT b.*,
                   (SELECT COUNT(*) FROM issued_books ib WHERE ib.book_id = b.id) as total_issues
            FROM books b
            WHERE b.quantity > 0";
    if (!empty($excludeIds)) {
        $sql .= " AND b.id NOT IN (" . implode(',', array_map('intval', $excludeIds)) . ")";
    }
    $candidates = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

    // 5. Score each candidate: genre match weight + slight popularity boost
    foreach ($candidates as &$book) {
        $genre = $book['genre'];
        $score = 0;
        if (isset($genreCounts[$genre])) {
            $score += 3 + $genreCounts[$genre];
        }
        $score += 0.01 * (int)$book['total_issues'];
        $book['score']  = $score;
        $book['reason'] = isset($genreCounts[$genre])
            ? "Because you've read $genre books"
            : "You might also like this";
    }
    unset($book);

    // 6. Rank and return top N
    usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($candidates, 0, $limit);
}

// ---------------------------------------------------------------
// PAGE LOGIC
// ---------------------------------------------------------------
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$recommendations = [];
$memberName = null;

if ($member_id > 0) {
    $recommendations = get_ai_recommendations($conn, $member_id, 5);

    $stmt = $conn->prepare("SELECT name FROM members WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $memberName = $row['name'];
    }
    $stmt->close();
}
?>

<style>
    .ai-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
    .ai-lookup { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 25px; }
    .ai-lookup input[type=number] { padding: 8px; width: 150px; border: 1px solid #ccc; border-radius: 4px; }
    .ai-lookup button { padding: 8px 16px; background: #2c3e50; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    .ai-lookup button:hover { background: #1a252f; }
    .ai-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
    .ai-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.12); padding: 16px; }
    .ai-card h4 { margin: 0 0 8px; color: #2c3e50; }
    .ai-card p { margin: 4px 0; font-size: 14px; color: #444; }
    .ai-reason { display: inline-block; margin-top: 8px; font-size: 12px; background: #eaf2fb; color: #2c3e50; padding: 4px 8px; border-radius: 12px; }
    .ai-empty { background: #fff3cd; padding: 15px; border-radius: 6px; color: #856404; }
</style>

<div class="ai-container">
    <h2>AI Recommended Books</h2>

    <div class="ai-lookup">
        <form method="GET" action="ai_recommend.php">
            <label for="member_id">Enter Member ID:</label>
            <input type="number" name="member_id" id="member_id" value="<?= $member_id ?: '' ?>" required>
            <button type="submit">Get Recommendations</button>
        </form>
    </div>

    <?php if ($member_id > 0): ?>
        <h3>Recommendations for <?= $memberName ? htmlspecialchars($memberName) : "Member #$member_id" ?></h3>

        <?php if (empty($recommendations)): ?>
            <div class="ai-empty">No recommendations available — try a different member ID or add more books with genres set.</div>
        <?php else: ?>
            <div class="ai-grid">
                <?php foreach ($recommendations as $book): ?>
                    <div class="ai-card">
                        <h4><?= htmlspecialchars($book['title']) ?></h4>
                        <p><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
                        <p><strong>Genre:</strong> <?= htmlspecialchars($book['genre']) ?></p>
                        <p><strong>In Stock:</strong> <?= (int)$book['quantity'] ?></p>
                        <span class="ai-reason"><?= htmlspecialchars($book['reason']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p>Enter a member ID above to see personalized AI book recommendations.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>