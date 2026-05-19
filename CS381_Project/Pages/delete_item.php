<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

// CSRF check via GET token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);// Forbidden
    die('Invalid CSRF token. Please go back and try again.');//stop if token is invalid
}

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);//id and type default 0 and lost
$type = $_GET['type'] ?? 'lost';

if ($id) {
    $table = $type === 'found' ? 'found_items' : 'lost_items'; // table based on type

    // Check item exists and belongs to user
    $stmt = $pdo->prepare("SELECT user_id, image_path FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $row  = $stmt->fetch();

    if ($row && ($row['user_id'] == $_SESSION['user_id'] || isAdmin())) {

        // Delete image file if exists
        if ($row['image_path'] && file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }

        // Delete from db
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Redirect to dashboard section based on type 
header('Location: dashboard.php?section=' . ($type === 'lost' ? 'my-lost' : 'my-found'));
exit();
?>