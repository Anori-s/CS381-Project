<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'lost';

if ($id) {
    $table = $type === 'found' ? 'found_items' : 'lost_items';

    // Get current status
    $stmt = $pdo->prepare("SELECT user_id, status FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    // Only owner or admin can change status
    if ($row && ($row['user_id'] == $_SESSION['user_id'] || isAdmin())) {
        $newStatus = $row['status'] === 'resolved' ? 'active' : 'resolved';
        $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
    }
}

header('Location: dashboard.php?section=' . ($type === 'lost' ? 'my-lost' : 'my-found'));
exit();
?>
