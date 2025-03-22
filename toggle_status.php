<?php
require_once 'config.php';

if (!isset($_GET['user_id']) die("Invalid request"));
$user_id = (int)$_GET['user_id'];
$type = $_GET['type'] ?? 'student';

$stmt = $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ?");
$stmt->execute([$user_id]);

header("Location: librarian_dashboard.php?page=" . ($type === 'faculty' ? 'faculty' : 'students'));
exit();