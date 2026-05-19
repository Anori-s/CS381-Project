<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin(); // Must be logged in to report

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // same as report_found
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $date_lost = trim($_POST['date_lost']);
    $description = trim($_POST['description']);
    $contact = trim($_POST['contact']);
    $reward = trim($_POST['reward']);

    // Validation
    if (empty($title)) $errors[] = 'Item name is required';
    if (empty($category)) $errors[] = 'Category is required';
    if (empty($location)) $errors[] = 'Location is required';
    if (empty($date_lost)) $errors[] = 'Date is required';
    if (strlen($description) < 20) $errors[] = 'Description must be at least 20 characters';
    if (!filter_var($contact, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email';

    // image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['image']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Only JPG, PNG, or WEBP images are allowed.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be 5 MB or smaller.';
        } else {
            $ext= pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_') . '.' . $ext;
            $image_path = 'uploads/items/' . $filename;
            if (!is_dir('../uploads/items/')) {
                mkdir('../uploads/items/', 0755, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/items/' . $filename);
        }
    }

    // Insert into database
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO lost_items (user_id, title, category, location, date_lost, description, contact, reward, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $title, $category, $location, $date_lost,
            $description, $contact, $reward, $image_path
        ]);

        header('Location: index.php?tab=lost');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - YIC Lost &amp; Found</title>
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
        <li><a href="report_lost.php" class="active">Report Lost</a></li>
        <li><a href="report_found.php">Post Found</a></li>
        <li><a href="dashboard.php">My Account</a></li>
    </ul></nav>
    <div class="top-bar-right">
        <span class="user-name-badge">👤 <?php echo e($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-white btn-small">Logout</a>
    </div>
</header>

<p class="page-trail"><a href="index.php">Home</a> › Report Lost Item</p>

<div class="form-box">
    <h2>📋 Report a Lost Item</h2>
    <p class="form-note">Fill in the details below. Your report will appear on the home page.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $err): ?>
                <p>⚠️ <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="report_lost.php" enctype="multipart/form-data">
        <?php csrfField(); ?>

        <div class="two-columns">
            <div class="input-group">
                <label for="title">Item Name *</label>
                <input type="text" id="title" name="title"
                       value="<?php echo e($_POST['title'] ?? ''); ?>"
                       placeholder="e.g. iPhone 16" required>
            </div>
            <div class="input-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="">-- Select --</option>
                    <option value="electronics" <?php if (($_POST['category'] ?? '') === 'electronics') echo 'selected'; ?>>Electronics</option>
                    <option value="bags"<?php if (($_POST['category'] ?? '') === 'bags') echo 'selected'; ?>>Bags and Luggage</option>
                    <option value="clothing"<?php if (($_POST['category'] ?? '') === 'clothing')echo 'selected'; ?>>Clothing</option>
                    <option value="keys"<?php if (($_POST['category'] ?? '') === 'keys') echo 'selected'; ?>>Keys</option>
                    <option value="accessories" <?php if (($_POST['category'] ?? '') === 'accessories') echo 'selected'; ?>>Accessories</option>
                    <option value="books" <?php if (($_POST['category'] ?? '') === 'books') echo 'selected'; ?>>Books and Stationery</option>
                    <option value="other" <?php if (($_POST['category'] ?? '') === 'other')echo 'selected'; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="two-columns">
            <div class="input-group">
                <label for="location">Last Seen Location *</label>
                <input type="text" id="location" name="location"
                       value="<?php echo e($_POST['location'] ?? ''); ?>"
                       placeholder="e.g. Lab B0-15" required>
            </div>
            <div class="input-group">
                <label for="date_lost">Date Lost *</label>
                <input type="date" id="date_lost" name="date_lost"
                       value="<?php echo e($_POST['date_lost'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="input-group">
            <label for="description">Description * (min. 20 characters)</label>
            <textarea id="description" name="description" rows="4"
                      placeholder="Describe the item: colour, brand, size, any special markings..." required><?php echo e($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="input-group">
            <label for="contact">Contact Email *</label>
            <input type="email" id="contact" name="contact"
                   value="<?php echo e($_POST['contact'] ?? $_SESSION['user_email']); ?>"
                   placeholder="your@rcjy.edu.sa" required>
        </div>

        <div class="input-group">
            <label for="reward">Reward Offer (optional)</label>
            <input type="text" id="reward" name="reward"
                   value="<?php echo e($_POST['reward'] ?? ''); ?>"
                   placeholder="e.g. 50 SAR reward">
        </div>

        <div class="input-group">
            <label>Photo (optional / recommended)</label>
            <input type="file" id="photo-input" name="image" accept="image/*" style="display:none">
            <label for="photo-input">
                <div class="photo-upload" id="upload-zone">
                    <div class="cam-icon">📷</div>
                    <p>Click or drag a photo here</p>
                    <p>JPG, PNG — max 5 MB</p>
                </div>
            </label>
            <div class="photo-preview" id="img-preview" style="display:none">
                <img src="" alt="Preview">
                <button type="button" class="remove-photo">✕</button>
            </div>
        </div>

        <div class="form-bottom-btns">
            <a href="index.php" class="btn btn-white">Cancel</a>
            <button type="submit" class="btn btn-blue">Submit Report →</button>
        </div>
    </form>
</div>

<footer><p>&copy; 2026 YIC Lost &amp; Found Portal | CS381 Project</p></footer>
<div class="notifications-area" id="toast-area"></div>
<script src="../JS/main.js"></script>
</body>
</html>