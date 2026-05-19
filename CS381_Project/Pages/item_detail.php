<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'lost'; //gwt and validate id and type

if (!$id) {
    header('Location: index.php');
    exit();
}

// Item fetch
if ($type === 'found') {
    $stmt = $pdo->prepare("SELECT f.*, u.name AS reporter_name FROM found_items f JOIN users u ON u.id = f.user_id WHERE f.id = ?");
} else {
    $stmt = $pdo->prepare("SELECT l.*, u.name AS reporter_name FROM lost_items l JOIN users u ON u.id = l.user_id WHERE l.id = ?");
}
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: index.php');
    exit();
}

// Message form submission
$msgSuccess = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //form sub
    verifyCsrf();//veriify token

    $sender_name  = trim($_POST['sender_name']);
    $sender_email = trim($_POST['sender_email']);
    $body= trim($_POST['body']); //trim space

    if (empty($sender_name) || !filter_var($sender_email, FILTER_VALIDATE_EMAIL) || strlen($body) < 20) {
        $msgError = 'Please fill in all fields correctly (message min. 20 characters).'; //validate
    } else { //allc correct insert
        $stmt = $pdo->prepare("INSERT INTO messages (item_id, item_type, sender_name, sender_email, body) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $type, $sender_name, $sender_email, $body]);
        $msgSuccess = 'Your message was sent successfully!';
    }
}

// Category icon
function categoryIcon($cat) {
    $icons = [
        'electronics' => '🔌', 'bags' => '🎒', 'clothing' => '👕',
        'keys' => '🔑', 'accessories' => '⌚', 'books' => '📚', 'other' => '📦'
    ];
    return $icons[$cat] ?? '📦'; //default
}

$tagClass = $item['status'] === 'resolved' ? 'tag-resolved' : ($type === 'lost' ? 'tag-lost' : 'tag-found'); //if item resolved resolved else lost or found
$tagText = $item['status'] === 'resolved' ? 'Resolved' : ucfirst($type); //same for text
$dateLabel = $type === 'lost' ? 'Date Lost' : 'Date Found';//date label based on type
$dateVal= $type === 'lost' ? $item['date_lost'] : $item['date_found']; //date value 
$isLost = $type === 'lost';
$personLbl = $isLost ? 'Reported by' : 'Found by'; //button label based on type
$btnLabel = $isLost ? '✋ I Found This Item' : '📩 This Is Mine';
$heldLabels = ['self' => 'With the finder', 'security' => 'Security Office', 'dept' => 'Department Office', 'other' => 'Other'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($item['title']); ?> - YIC Lost &amp; Found</title>
    <link rel="stylesheet" href="../Style/style.css">
</head>
<body>

<header class="top-bar">
    <div class="logo-area">
        <img src="YIC_Logo.png" alt="YIC Logo">
        <div>
            <h1>YIC Lost &amp; Found</h1>
            <p>Yanbu Industrial College</p>
        </div>
    </div>
    <nav><ul><!-- sa,e nav-->
        <li><a href="index.php">Home</a></li>
        <li><a href="report_lost.php">Report Lost</a></li>
        <li><a href="report_found.php">Post Found</a></li>
        <?php if (isLoggedIn()): ?>
            <li><a href="dashboard.php">My Account</a></li>
        <?php endif; ?>
    </ul></nav>
    <div class="top-bar-right">
        <?php if (isLoggedIn()): ?>
            <span class="user-name-badge">👤 <?php echo e($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="btn btn-white btn-small">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-blue btn-small">Login</a>
        <?php endif; ?>
    </div>
</header>

<p class="page-trail">
    <a href="index.php">Home</a> ›
    <a href="index.php?tab=<?php echo $type; ?>"><?php echo $isLost ? 'Lost Items' : 'Found Items'; ?></a> ›
    <?php echo e($item['title']); ?>
</p>

<div class="detail-page">

    <div>
        <div class="detail-card">
            <div class="detail-big-photo">
                <?php if ($item['image_path']): ?>
                    <img src="<?php echo e('../' . $item['image_path']); ?>" alt="<?php echo e($item['title']); ?>">
                <?php else: ?>
                    <span style="font-size:80px"><?php echo categoryIcon($item['category']); ?></span>
                <?php endif; ?>
            </div>
            <div class="detail-text">
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                    <span class="tag <?php echo $isLost ? 'tag-lost' : 'tag-found'; ?>"><?php echo $isLost ? 'Lost' : 'Found'; ?></span>
                    <span class="tag <?php echo $tagClass; ?>"><?php echo $tagText; ?></span>
                </div>
                <h1><?php echo e($item['title']); ?></h1>
                <div class="detail-info-row">
                    <span>📍 <?php echo e($item['location']); ?></span>
                    <span>📅 <?php echo $dateLabel; ?>: <?php echo e($dateVal); ?></span>
                    <span>👤 <?php echo $personLbl; ?>: <?php echo e($item['reporter_name']); ?></span>
                </div>
                <div class="small-heading">Description</div>
                <p><?php echo e($item['description']); ?></p>
                <?php if (!empty($item['reward'])): ?>
                    <div class="small-heading">Reward</div>
                    <p>💰 <?php echo e($item['reward']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="right-column">

        <?php if ($item['status'] === 'resolved'): ?>
            <div class="side-card">
                <p>✅ This item has been resolved.</p>
            </div>
        <?php else: ?>
            <div class="side-card" style="border:2px solid #3b82f6">
                <h3><?php echo $isLost ? 'Found This Item?' : 'Is This Yours?'; ?></h3>
                <p style="font-size:13px;color:#6b8ab0;margin-bottom:14px">
                    <?php echo $isLost
                        ? 'If you found this item, send a message to the owner and they will verify your claim.'
                        : 'If this is your item, send a message to the finder and prove ownership by describing it.'; ?>
                </p>

                <?php if ($msgSuccess): ?>
                    <div class="info-box info-box-green" style="margin-bottom:12px">✅ <?php echo e($msgSuccess); ?></div>
                <?php endif; ?>
                <?php if ($msgError): ?>
                    <div class="error-box" style="margin-bottom:12px"><p>⚠️ <?php echo e($msgError); ?></p></div>
                <?php endif; ?>

                <form method="POST" action="item_detail.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>">
                    <?php csrfField(); ?> <!-- CSRF token -->
                    <div class="input-group">
                        <label for="sender_name">Your Name <span class="required-star">*</span></label>
                        <input type="text" id="sender_name" name="sender_name"
                               value="<?php echo e(isLoggedIn() ? $_SESSION['user_name'] : ($_POST['sender_name'] ?? '')); ?>"
                               placeholder="Your full name" required> <!-- prefill if logged in or after submit -->
                    </div>
                    <div class="input-group">
                        <label for="sender_email">Your Email <span class="required-star">*</span></label>
                        <input type="email" id="sender_email" name="sender_email"
                               value="<?php echo e(isLoggedIn() ? $_SESSION['user_email'] : ($_POST['sender_email'] ?? '')); ?>"
                               placeholder="your@rcjy.edu.sa" required>
                    </div>
                    <div class="input-group">
                        <label for="body">Message <span class="required-star">*</span></label>
                        <textarea id="body" name="body" rows="4"
                                  placeholder="Explain why this item is yours, or how you can help..." required><?php echo e($_POST['body'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-bottom-btns">
                        <button type="submit" class="btn btn-blue"><?php echo $btnLabel; ?></button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="side-card">
            <h3>Item Details</h3>
            <div class="key-value-list">
                <div class="kv-row"><span class="kv-label">Category</span><span class="kv-data"><?php echo e($item['category']); ?></span></div>
                <div class="kv-row"><span class="kv-label">Date</span><span class="kv-data"><?php echo e($dateVal); ?></span></div>
                <div class="kv-row"><span class="kv-label">Location</span><span class="kv-data"><?php echo e($item['location']); ?></span></div>
                <div class="kv-row"><span class="kv-label">Status</span><span class="kv-data"><?php echo $tagText; ?></span></div>
                <?php if ($isLost && !empty($item['reward'])): ?>
                    <div class="kv-row"><span class="kv-label">Reward</span><span class="kv-data">💰 <?php echo e($item['reward']); ?></span></div>
                <?php endif; ?>
                <?php if (!$isLost && $item['held_at']): ?>
                    <div class="kv-row"><span class="kv-label">Item held at</span><span class="kv-data"><?php echo e($heldLabels[$item['held_at']] ?? $item['held_at']); ?></span></div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<footer><p>&copy; 2026 YIC Lost &amp; Found Portal | CS381 Project</p></footer>
<div class="notifications-area" id="toast-area"></div>
<script src="../JS/main.js"></script>
</body>
</html>