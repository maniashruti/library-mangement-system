<?php
require_once 'config.php';

$user_id = (int)$_GET['user_id'];
$stmt = $pdo->prepare("SELECT t.*, b.title 
                      FROM transactions t
                      JOIN books b ON t.book_id = b.id
                      WHERE t.user_id = ?
                      ORDER BY t.issue_date DESC");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Book Title</th>
            <th>Issue Date</th>
            <th>Due Date</th>
            <th>Return Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['title']) ?></td>
            <td><?= date('M d, Y', strtotime($t['issue_date'])) ?></td>
            <td><?= date('M d, Y', strtotime($t['due_date'])) ?></td>
            <td><?= $t['return_date'] ? date('M d, Y', strtotime($t['return_date'])) : 'Not Returned' ?></td>
            <td><?= ucfirst($t['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>