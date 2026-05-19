<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

$id  = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'lost';

// Fetch item
$table = $type === 'found' ? 'found_items' : 'lost_items';
$stmt  = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->execute([$id]);
$item  = $stmt->fetch();

//no item = go to dashboard
if (!$item) {
    header('Location: dashboard.php');
    exit();
}

// Only owner or admin can edit else dash
if ($item['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') { //form sub
    verifyCsrf(); //verify token

    $title = trim($_POST['title']); //trim and clean inputs
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $contact = trim($_POST['contact']);
    $status = trim($_POST['status']);

    // Validation
    if (empty($title)) $errors[] = 'Item name is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';
    if (!filter_var($contact, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid contact email required.';

    if (empty($errors)) {// no errors, update db
        if ($type === 'found') {
            $stmt = $pdo->prepare("UPDATE found_items SET title=?, category=?, location=?, description=?, contact=?, status=? WHERE id=?");
            $stmt->execute([$title, $category, $location, $description, $contact, $status, $id]);
        } else {
            $reward = trim($_POST['reward']);
            $stmt   = $pdo->prepare("UPDATE lost_items SET title=?, category=?, location=?, description=?, contact=?, reward=?, status=? WHERE id=?");
            $stmt->execute([$title, $category, $location, $description, $contact, $reward, $status, $id]);
        }

        header('Location: dashboard.php?section=' . ($type === 'lost' ? 'my-lost' : 'my-found'));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - YIC Lost &amp; Found</title>
    <link rel="stylesheet" href="../Style/style.css">
</head>
<body>

<header class="top-bar">
    <div class="logo-area">
        <img src="YIC_Logo.png" alt="YIC Logo">
        <div><h1>YIC Lost &amp; Found</h1><p>Yanbu Industrial College</p></div>
    </div>
    <nav><ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="dashboard.php">My Account</a></li>
    </ul></nav>
    <div class="top-bar-right">
        <span class="user-name-badge">👤 <?php echo e($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-white btn-small">Logout</a>
    </div>
</header>

<p class="page-trail"><a href="dashboard.php">Dashboard</a> › Edit Item</p>

<div class="form-box">
    <h2>✏️ Edit <?php echo $type === 'lost' ? 'Lost Report' : 'Found Post'; ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $err): ?>
                <p>⚠️ <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_item.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>">
        <?php csrfField(); ?>

        <div class="input-group">
            <label for="title">Item Name *</label>
            <input type="text" id="title" name="title"
                   value="<?php echo e($_POST['title'] ?? $item['title']); ?>" required>
        </div>

        <div class="two-columns">
            <div class="input-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <?php
                        $cats = ['electronics','bags','clothing','keys','accessories','books','other'];//categories
                        $current = $_POST['category'] ?? $item['category'];//current value for selection
                        foreach ($cats as $cat): //loop cats
                            $sel = $current === $cat ? 'selected' : ''; 
                    ?>
                        <option value="<?php echo $cat; ?>" <?php echo $sel; ?>><?php echo ucfirst($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php
                        $statuses  = ['active','resolved','pending'];
                        $currentSt = $_POST['status'] ?? $item['status'];
                        foreach ($statuses as $st):
                            $sel = $currentSt === $st ? 'selected' : '';
                    ?>
                        <option value="<?php echo $st; ?>" <?php echo $sel; ?>><?php echo ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="input-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location"
                   value="<?php echo e($_POST['location'] ?? $item['location']); ?>" required>
        </div>

        <div class="input-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="4" required><?php echo e($_POST['description'] ?? $item['description']); ?></textarea>
        </div>

        <div class="input-group">
            <label for="contact">Contact Email *</label>
            <input type="email" id="contact" name="contact"
                   value="<?php echo e($_POST['contact'] ?? $item['contact']); ?>" required>
        </div>

        <?php if ($type === 'lost'): ?>
        <div class="input-group">
            <label for="reward">Reward (optional)</label>
            <input type="text" id="reward" name="reward"
                   value="<?php echo e($_POST['reward'] ?? $item['reward']); ?>">
        </div>
        <?php endif; ?>

        <div class="form-bottom-btns">
            <a href="dashboard.php?section=<?php echo $type === 'lost' ? 'my-lost' : 'my-found'; ?>" class="btn btn-white">Cancel</a>
            <button type="submit" class="btn btn-blue">Save Changes</button>
        </div>
    </form>
</div>

<footer><p>&copy; 2026 YIC Lost &amp; Found Portal | CS381 Project</p></footer>
</body>
</html>