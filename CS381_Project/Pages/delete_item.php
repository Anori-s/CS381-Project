<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'lost';

if ($id) {
    $table = $type === 'found' ? 'found_items' : 'lost_items';

    //check item exists and for use
    $stmt = $pdo->prepare("SELECT user_id, image_path FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $row  = $stmt->fetch();
    if ($row && ($row['user_id'] == $_SESSION['user_id'] || isAdmin())) {

        //Delete image file 
        if ($row['image_path'] && file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }

        //Delete query
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
    }
}

//Redirect to dashboard
header('Location: dashboard.php?section=' . ($type === 'lost' ? 'my-lost' : 'my-found'));
exit();
?>
