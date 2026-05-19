<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin(); // Must be logged in

if (isAdmin()) {
    header('Location: admin.php');
    exit();
} //redirect admins to admin panel
$userId = $_SESSION['user_id'];

//Fetch user lost items
$stmt= $pdo->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$myLost= $stmt->fetchAll();

//found items
$stmt= $pdo->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$myFound= $stmt->fetchAll();

// Count resolved
$resolvedCount = 0;
foreach (array_merge($myLost, $myFound) as $item) {
    if ($item['status'] === 'resolved') $resolvedCount++;
}

$section = $_GET['section'] ?? 'overview';//section default to overview

$profileSuccess = '';
$profileErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    //post check if submission and not just visit + update form
    verifyCsrf(); //check csrf token in submission

    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);
    $currPass= $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($newName)) $profileErrors[] = 'Name is required.'; //errors in array
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $profileErrors[] = 'Valid email required.';
// filter_vaer return false if not valid email format 
    if ($newPass) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!password_verify($currPass, $row['password'])) {//check if current password match db
            $profileErrors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {//check length
            $profileErrors[] = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {//compare
            $profileErrors[] = 'Passwords do not match.';
        }
    }

    if (empty($profileErrors)) { //no issues so update
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);//hash new pass
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
            $stmt->execute([$newName, $newEmail, $hash, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $stmt->execute([$newName, $newEmail, $userId]); 
        }
        $_SESSION['user_name'] = $newName;//update session
        $_SESSION['user_email'] = $newEmail;
        $profileSuccess = 'Profile updated successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - YIC Lost &amp; Found</title>
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
        <li><a href="report_lost.php">Report Lost</a></li>
        <li><a href="report_found.php">Post Found</a></li>
        <li><a href="dashboard.php" class="active">My Account</a></li>
    </ul></nav>
    <div class="top-bar-right">
        <span class="user-name-badge">👤 <?php echo e($_SESSION['user_name']); ?></span><!-- show name from session -->
        <a href="logout.php" class="btn btn-white btn-small">Logout</a>
    </div>
</header>

<div class="dashboard-page">

    <!-- Left side-->
    <div class="dash-left-panel">
        <div class="dash-user-area">
            <div class="user-initials">
                <?php
                    $parts = explode(' ', $_SESSION['user_name']);//split into array
                    echo strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                    //get first litter of each into capital
                ?>
            </div>
            <div class="user-full-name"><?php echo e($_SESSION['user_name']); ?></div>
            <div class="user-email-small"><?php echo e($_SESSION['user_email']); ?></div>
        </div>
        <nav class="dash-menu">
            <a href="dashboard.php?section=overview" class="dash-menu-item <?php echo $section === 'overview' ? 'active' : ''; ?>">📊 Overview</a>
            <a href="dashboard.php?section=my-lost"  class="dash-menu-item <?php echo $section === 'my-lost' ? 'active' : ''; ?>">📋 My Lost Reports</a>
            <a href="dashboard.php?section=my-found" class="dash-menu-item <?php echo $section === 'my-found' ? 'active' : ''; ?>">📌 My Found Posts</a>
            <a href="dashboard.php?section=profile"  class="dash-menu-item <?php echo $section === 'profile' ? 'active' : ''; ?>">⚙️ Profile</a>
            <a href="logout.php" class="dash-menu-item logout">↩ Logout</a>
        </nav>
    </div>

    <div style="flex:1">

        <!--Overview-->
        <?php if ($section === 'overview'): ?>
        <div class="dash-section active">
            <div class="panel-top-row">
                <h2>My Overview</h2>
                <div style="display:flex;gap:8px">
                    <a href="report_lost.php"  class="btn btn-small btn-white">+ Report Lost</a>
                    <a href="report_found.php" class="btn btn-small btn-blue">+ Post Found</a>
                </div>
            </div>
            <div class="stat-cards-row">
                <div class="stat-card">
                    <div class="big-number"><?php echo count($myLost); ?></div>
                    <div class="small-label">Lost Reports</div>
                </div>
                <div class="stat-card green">
                    <div class="big-number"><?php echo count($myFound); ?></div>
                    <div class="small-label">Found Posts</div>
                </div>
                <div class="stat-card orange">
                    <div class="big-number"><?php echo $resolvedCount; ?></div>
                    <div class="small-label">Resolved</div>
                </div>
            </div>

            <div class="white-box">
                <h3>Recent Activity</h3>
                <?php
                    $combined = [];
                    foreach ($myLost as $i) $combined[] = array_merge($i, ['_type' => 'lost']);
                    foreach ($myFound as $i) $combined[] = array_merge($i, ['_type' => 'found']);
                    usort($combined, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));//compare date as string to sort
                    $recent = array_slice($combined, 0, 5); //loop my lost and founs and het 5 recent
                ?>
                <?php if (empty($recent)): ?>
                    <p style="color:#6b8ab0;font-size:13px">No activity yet.</p>
                <?php else: ?>
                    <?php foreach ($recent as $item): ?>
                        <?php
                            $typ = $item['_type'];
                            $cls = $item['status'] === 'resolved' ? 'tag-resolved' : ($typ === 'lost' ? 'tag-lost' : 'tag-found');
                            $lbl = $item['status'] === 'resolved' ? 'Resolved' : ucfirst($typ);//resovled or type
                        ?>
                        <div class="my-item-row">
                            <div class="my-item-text">
                                <div class="item-name"><?php echo e($item['title']); ?></div>
                                <div class="item-sub"><?php echo ucfirst($typ); ?> · <?php echo e($item['location']); ?></div>
                            </div>
                            <span class="tag <?php echo $cls; ?>"><?php echo $lbl; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!--Lost Reports-->
        <?php elseif ($section === 'my-lost'): ?>
        <div class="dash-section active">
            <div class="panel-top-row">
                <h2>My Lost Reports</h2>
                <a href="report_lost.php" class="btn btn-small btn-blue">+ New Report</a>
            </div>
            <?php if (empty($myLost)): ?>
                <div class="nothing-yet">
                    <div class="big-emoji">📋</div>
                    <h3>Nothing here yet</h3>
                </div>
            <?php else: ?>
                <?php foreach ($myLost as $item): ?>
                    <?php $cls = $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-lost'; ?>
                    <div class="my-item-row">
                        <div class="my-item-text">
                            <div class="item-name"><?php echo e($item['title']); ?></div>
                            <div class="item-sub"><?php echo e($item['location']); ?> · <?php echo e($item['date_lost']); ?></div>
                        </div>
                        <div class="my-item-buttons">
                            <span class="tag <?php echo $cls; ?>"><?php echo $item['status']; ?></span>
                            <a href="item_detail.php?id=<?php echo $item['id']; ?>&type=lost" class="btn btn-small btn-white">View</a>
                            <a href="edit_item.php?id=<?php echo $item['id']; ?>&type=lost"   class="btn btn-small btn-blue">Edit</a>
                            <a href="delete_item.php?id=<?php echo $item['id']; ?>&type=lost&csrf_token=<?php echo csrfToken(); ?>"
   class="btn btn-small btn-red"
   onclick="return confirm('Delete this item?')">Delete</a><!--delete with confirmation and csrf token-->
                            <a href="toggle_status.php?id=<?php echo $item['id']; ?>&type=lost&csrf_token=<?php echo csrfToken(); ?>"
                               class="btn btn-small btn-green">
                               <?php echo $item['status'] === 'resolved' ? 'Reopen' : 'Mark Resolved'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!--Found Posts-->
        <?php elseif ($section === 'my-found'): ?>
        <div class="dash-section active">
            <div class="panel-top-row">
                <h2>My Found Posts</h2>
                <a href="report_found.php" class="btn btn-small btn-blue">+ Post Found</a>
            </div>
            <?php if (empty($myFound)): ?>
                <div class="nothing-yet"><div class="big-emoji">📌</div><h3>Nothing here yet</h3></div>
            <?php else: ?>
                <?php foreach ($myFound as $item): ?>
                    <?php $cls = $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-found'; ?>
                    <div class="my-item-row">
                        <div class="my-item-text">
                            <div class="item-name"><?php echo e($item['title']); ?></div>
                            <div class="item-sub"><?php echo e($item['location']); ?> · <?php echo e($item['date_found']); ?></div>
                        </div>
                        <div class="my-item-buttons">
                            <span class="tag <?php echo $cls; ?>"><?php echo $item['status']; ?></span>
                            <a href="item_detail.php?id=<?php echo $item['id']; ?>&type=found" class="btn btn-small btn-white">View</a>
                            <a href="edit_item.php?id=<?php echo $item['id']; ?>&type=found"   class="btn btn-small btn-blue">Edit</a>
                            <a href="delete_item.php?id=<?php echo $item['id']; ?>&type=found&csrf_token=<?php echo csrfToken(); ?>"
                               class="btn btn-small btn-red"
                               onclick="return confirm('Delete this item?')">Delete</a>
                            <a href="toggle_status.php?id=<?php echo $item['id']; ?>&type=found&csrf_token=<?php echo csrfToken(); ?>"
                               class="btn btn-small btn-green">
                               <?php echo $item['status'] === 'resolved' ? 'Reopen' : 'Mark Resolved'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!--Profile-->
        <?php elseif ($section === 'profile'): ?>
        <div class="dash-section active">
            <h2>⚙️ Profile Settings</h2><br>

            <?php if ($profileSuccess): ?>
                <div class="info-box info-box-green" style="margin-bottom:16px">✅ <?php echo e($profileSuccess); ?></div>
            <?php endif; ?>
            <?php if (!empty($profileErrors)): ?><!--show errors if any-->
                <div class="error-box" style="margin-bottom:16px">
                    <?php foreach ($profileErrors as $err): ?>
                        <p>⚠️ <?php echo e($err); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="white-box">
                <form method="POST" action="dashboard.php?section=profile">
                    <?php csrfField(); ?>
                    <input type="hidden" name="update_profile" value="1">
                    <div class="input-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name"
                               value="<?php echo e($_SESSION['user_name']); ?>" required><!-- pre-fill with session data -->
                    </div>
                    <div class="input-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo e($_SESSION['user_email']); ?>" required>
                    </div>
                    <div class="line-divider"></div>
                    <p style="font-size:13px;font-weight:600;margin-bottom:14px">Change Password (leave blank to keep current)</p>
                    <div class="input-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                    </div>
                    <div class="input-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters">
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
                    </div>
                    <div class="form-bottom-btns">
                        <button type="submit" class="btn btn-blue">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<footer><p>&copy; 2026 YIC Lost &amp; Found Portal | CS381 Project</p></footer>
</body>
</html>