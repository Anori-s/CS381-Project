<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

//Handle search & filter
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$tab = $_GET['tab'] ?? 'lost';  // 'lost' or 'found'

// lost items query
$lostSql = "SELECT l.*, u.name AS owner_name FROM lost_items l JOIN users u ON u.id = l.user_id WHERE 1=1";
$lostParams = [];

if ($search !== '') {// Search by title or location
    $lostSql .= " AND (l.title LIKE ? OR l.location LIKE ?)";
    $lostParams[] = "%$search%"; 
    $lostParams[] = "%$search%";
}
if ($category !== '') { // Filter by category
    $lostSql .= " AND l.category = ?";
    $lostParams[] = $category;
}
$lostSql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($lostSql);
$stmt->execute($lostParams);
$lostItems = $stmt->fetchAll();

//found items
$foundSql = "SELECT f.*, u.name AS finder_name FROM found_items f JOIN users u ON u.id = f.user_id WHERE 1=1";
$foundParams = [];

if ($search !== '') { 
    $foundSql .= " AND (f.title LIKE ? OR f.location LIKE ?)";
    $foundParams[] = "%$search%";//if contains a word, add to param
    $foundParams[] = "%$search%";
}
if ($category !== '') {
    $foundSql .= " AND f.category = ?";
    $foundParams[] = $category;
}
$foundSql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($foundSql);
$stmt->execute($foundParams);//
$foundItems = $stmt->fetchAll();

//Counters
$totalLost= $pdo->query("SELECT COUNT(*) FROM lost_items  WHERE status != 'resolved'")->fetchColumn();
$totalFound = $pdo->query("SELECT COUNT(*) FROM found_items WHERE status != 'resolved'")->fetchColumn();
$totalResolved = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE status='resolved') + (SELECT COUNT(*) FROM found_items WHERE status='resolved')")->fetchColumn();

// Category icons
function categoryIcon($cat) {
    $icons = [
        'electronics' => '🔌', 'bags' => '🎒', 'clothing' => '👕',
        'keys' => '🔑', 'accessories' => '⌚', 'books' => '📚', 'other' => '📦'
    ];
    return $icons[$cat] ?? '📦';//default
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YIC Lost &amp; Found</title>
    <link rel="stylesheet" href="../Style/style.css">
</head>
<body>

<!-- Header -->
<header class="top-bar">
    <div class="logo-area">
        <img src="YIC_Logo.png" alt="YIC Logo">
        <div>
            <h1>YIC Lost &amp; Found</h1>
            <p>Yanbu Industrial College</p>
        </div>
    </div>
    <nav>
        <ul>
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="report_lost.php">Report Lost</a></li>
            <li><a href="report_found.php">Post Found</a></li>
            <?php if (isLoggedIn()): ?><!-- Show dashboard link if logged in -->
                <li><a href="dashboard.php">My Account</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="top-bar-right">
        <?php if (isLoggedIn()): ?><!-- Show user badge and logout if logged in -->
            <span class="user-name-badge">👤 <?php echo e($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="btn btn-white btn-small">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-blue btn-small">Login</a>
        <?php endif; ?>
    </div>
</header>

<!--Hero -->
<section class="hero-section">
    <h2>Lost Something? Found Something?</h2>
    <p>Report lost items or post found items at Yanbu Industrial College</p>
    <div class="hero-buttons">
        <a href="report_lost.php" class="btn btn-blue">📋 Report Lost Item</a>
        <a href="report_found.php" class="btn btn-white">📌 Post Found Item</a>
    </div>
</section>

<!-- Counters -->
<section class="counter-row">
    <div class="counter-box">
        <h3><?php echo $totalLost; ?></h3>
        <p>Lost Items</p>
    </div>
    <div class="counter-box">
        <h3><?php echo $totalFound; ?></h3>
        <p>Found Items</p>
    </div>
    <div class="counter-box">
        <h3><?php echo $totalResolved; ?></h3>
        <p>Resolved</p>
    </div>
</section>

<!-- Search -->
<section class="search-bar">
    <form method="GET" action="index.php" style="display:flex;gap:8px;width:100%">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>"><!--Keep tab on search -->
        <input type="text" name="q" placeholder="Search by name or location"
               value="<?php echo e($search); ?>"><!-- Search input -->
        <select name="category">
            <option value="">All Categories</option> <!-- Category filter,if clicked show all items -->
            <option value="electronics"  <?php if ($category === 'electronics')  echo 'selected'; ?>>Electronics</option>
            <option value="bags" <?php if ($category === 'bags') echo 'selected'; ?>>Bags and Luggage</option>
            <option value="clothing" <?php if ($category === 'clothing') echo 'selected'; ?>>Clothing</option>
            <option value="keys" <?php if ($category === 'keys') echo 'selected'; ?>>Keys</option>
            <option value="accessories" <?php if ($category === 'accessories') echo 'selected'; ?>>Accessories</option>
            <option value="books" <?php if ($category === 'books') echo 'selected'; ?>>Books and Stationery</option>
            <option value="other" <?php if ($category === 'other') echo 'selected'; ?>>Other</option>
        </select>
        <button type="submit" id="searchBtn">Search</button>
    </form>
</section>

<!-- Tabs -->
<div class="tab-row">
    <a href="index.php?tab=lost&q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"
       class="tab-btn <?php echo $tab === 'lost' ? 'active' : ''; ?>">Lost Items</a>
    <a href="index.php?tab=found&q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"
       class="tab-btn <?php echo $tab === 'found' ? 'active' : ''; ?>">Found Items</a>
</div>

<!-- Lost Items Panel -->
<?php if ($tab === 'lost'): ?>
<section class="content-panel visible">
    <div class="panel-top-row">
        <h3>Recently Lost Items</h3>
        <a href="report_lost.php" class="btn btn-small btn-blue">+ Report Lost</a>
    </div>
    <div class="cards-grid">
        <?php if (empty($lostItems)): ?>
            <div class="nothing-yet">
                <div class="big-emoji">🔍</div>
                <h3>No items found</h3>
                <p>Be the first to report a lost item.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lostItems as $item): ?>
                <?php
                    $tagClass = $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-lost';
                    $tagText = $item['status'] === 'resolved' ? 'Resolved' : 'Lost';
                ?>
                <div class="item-card" onclick="location.href='item_detail.php?id=<?php echo $item['id']; ?>&type=lost'">
                    <div class="card-image">
                        <?php if ($item['image_path']): ?>
                            <img src="<?php echo e('../' . $item['image_path']); ?>" alt="<?php echo e($item['title']); ?>"> <!-- img for item -->
                        <?php else: ?>
                            <span style="font-size:42px"><?php echo categoryIcon($item['category']); ?></span><!-- icon if no img exist-->
                        <?php endif; ?>
                    </div>
                    <div class="card-title"><?php echo e($item['title']); ?></div>
                    <div class="card-details">
                        📍 <?php echo e($item['location']); ?><br>
                        📅 Lost: <?php echo e($item['date_lost']); ?>
                    </div>
                    <span class="tag <?php echo $tagClass; ?>"><?php echo $tagText; ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Found Items-->
<?php else: ?>
<section class="content-panel visible">
    <div class="panel-top-row">
        <h3>Recently Found Items</h3>
        <a href="report_found.php" class="btn btn-small btn-blue">+ Post Found</a>
    </div>
    <div class="cards-grid">
        <?php if (empty($foundItems)): ?>
            <div class="nothing-yet">
                <div class="big-emoji">📦</div>
                <h3>No items found</h3>
                <p>Be the first to post a found item.</p>
            </div>
        <?php else: ?>
            <?php foreach ($foundItems as $item): ?>
                <?php
                    $tagClass = $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-found';
                    $tagText  = $item['status'] === 'resolved' ? 'Resolved' : 'Found';
                ?>
                <div class="item-card" onclick="location.href='item_detail.php?id=<?php echo $item['id']; ?>&type=found'">
                    <div class="card-image">
                        <?php if ($item['image_path']): ?>
<img src="<?php echo e('../' . $item['image_path']); ?>" alt="<?php echo e($item['title']); ?>">                <?php else: ?>
                            <span style="font-size:42px"><?php echo categoryIcon($item['category']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-title"><?php echo e($item['title']); ?></div>
                    <div class="card-details">
                        📍 <?php echo e($item['location']); ?><br>
                        📅 Found: <?php echo e($item['date_found']); ?>
                    </div>
                    <span class="tag <?php echo $tagClass; ?>"><?php echo $tagText; ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<footer><p>&copy; 2026 YIC Lost &amp; Found Portal | CS381 Project</p></footer>
<script src="../JS/main.js"></script>
</body>
</html>
