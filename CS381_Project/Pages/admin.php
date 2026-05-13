<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();// Only admins can access this page

$section = $_GET['section'] ?? 'overview';
//show numbers
$totalUsers= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLost= $pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
$totalFound= $pdo->query("SELECT COUNT(*) FROM found_items")->fetchColumn();
$unreadMessages= $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$resolved= $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE status='resolved') + (SELECT COUNT(*) FROM found_items WHERE status='resolved')")->fetchColumn();

$actionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Delete item
    if (isset($_POST['delete_item'])) {
        $delId = (int) $_POST['item_id'];
        $delType = $_POST['item_type'];
        $table= $delType === 'found' ? 'found_items' : 'lost_items';
        $stmt = $pdo->prepare("SELECT image_path FROM $table WHERE id = ?");
        $stmt->execute([$delId]);
        $row= $stmt->fetch();
        if ($row && $row['image_path'] && file_exists($row['image_path'])) unlink($row['image_path']);
//if img exists ddelet from server
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$delId]);
        $actionMsg= 'Item deleted'; //execute query and show message
    }

    // Change item status
    if (isset($_POST['change_status'])) {
        $delId = (int) $_POST['item_id'];
        $delType = $_POST['item_type'];
        $newStatus = $_POST['new_status'];
        $table= $delType === 'found' ? 'found_items' : 'lost_items';
        $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?")->execute([$newStatus, $delId]);
        $actionMsg ='Status is updated';
    }

    // Delete message
    if (isset($_POST['delete_message'])) {
        $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([(int)$_POST['msg_id']]);
        $actionMsg = 'Message deleted';
    }

    // Mark message read
    if (isset($_POST['mark_read'])) {
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([(int)$_POST['msg_id']]);
        $actionMsg = 'Message marked read';
    }

    // Change user role
    if (isset($_POST['set_role'])) {
        $targetId= (int) $_POST['target_user_id'];
        $newRole = $_POST['new_role'];
        if ($targetId != $_SESSION['user_id']) {   // Can't change own role== security
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
            $actionMsg = 'User role updated';
        }
    }

    //Delete user
    if (isset($_POST['delete_user'])) {
        $targetId = (int) $_POST['target_user_id'];
        if ($targetId != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $actionMsg = 'User deleted';
        }
    }
}

$items= [];
$messages= [];
$users = []; //array objs 

if ($section === 'items') {
    $typeFilter = $_GET['type_filter'] ?? 'all'; 
    $statusFilter = $_GET['status_filter'] ?? 'all';

    //Get lost items
    if ($typeFilter !== 'found') {
        $sql = "SELECT l.*, u.name AS reporter, 'lost' AS item_type FROM lost_items l JOIN users u ON u.id = l.user_id WHERE 1=1";//show user as reporter
        $params = [];
        if ($statusFilter !== 'all') { $sql .= " AND l.status = ?"; $params[] = $statusFilter; }
        $stmt = $pdo->prepare($sql. " ORDER BY l.created_at DESC");//order by date desc
        $stmt->execute($params);
        $items = array_merge($items, $stmt->fetchAll());//merge lost and found items into one array 
    }
    //Get found items
    if ($typeFilter !== 'lost') {
        $sql = "SELECT f.*, u.name AS reporter, 'found' AS item_type FROM found_items f JOIN users u ON u.id = f.user_id WHERE 1=1";
        $params = [];
        if ($statusFilter !== 'all') { $sql .= " AND f.status = ?"; $params[] = $statusFilter; }
        $stmt = $pdo->prepare($sql . " ORDER BY f.created_at DESC");
        $stmt->execute($params);
        $items = array_merge($items, $stmt->fetchAll());
    }
    usort($items, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));//sort by date
}

if ($section=== 'messages') {//get messages with item title
    $messages = $pdo->query(
        "SELECT m.*,
             CASE WHEN m.item_type='lost' THEN l.title ELSE f.title END AS item_title
         FROM messages m
         LEFT JOIN lost_items  l ON l.id = m.item_id AND m.item_type = 'lost'
         LEFT JOIN found_items f ON f.id = m.item_id AND m.item_type = 'found'
         ORDER BY m.created_at DESC"
    )->fetchAll();
}

if ($section === 'users') {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

//Recent items for overview
$recentLost = $pdo->query("SELECT l.*, u.name AS reporter FROM lost_items  l JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 5")->fetchAll(); //limit 5 = only show 5 recent items
$recentFound = $pdo->query("SELECT f.*, u.name AS reporter FROM found_items f JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - YIC Lost &amp; Found</title>
    <link rel="stylesheet" href="../Style/style.css">
</head>
<body>

<div class="admin-page">

    <!-- Left side -->
    <div class="admin-left-bar">
        <div class="admin-logo-box">🛡️ Admin Panel</div>
        <nav class="admin-menu">
            <a href="admin.php?section=overview"  class="admin-menu-item <?php echo $section === 'overview'  ? 'active' : ''; ?>">📊 Overview</a>
            <a href="admin.php?section=items"     class="admin-menu-item <?php echo $section === 'items'     ? 'active' : ''; ?>">📦 All Items</a>
            <a href="admin.php?section=messages"  class="admin-menu-item <?php echo $section === 'messages'  ? 'active' : ''; ?>">
                ✉️ Messages
                <?php if ($unreadMessages > 0): ?>
                    <span style="background:#ef4444;color:#fff;border-radius:99px;padding:1px 7px;font-size:11px;margin-left:4px"><?php echo $unreadMessages; ?></span>
                <?php endif; ?> <!-- show unread messages count -->
            </a>
            <a href="admin.php?section=users" class="admin-menu-item <?php echo $section === 'users'? 'active' : ''; ?>">👥 Users</a>
            <a href="index.php" class="admin-menu-item">🏠 View Portal</a>
            <a href="logout.php" class="admin-menu-item">↩ Logout</a>
        </nav>
    </div>

    <div class="admin-main-area">

        <div class="admin-top-strip">
            <h1><?php echo ucfirst($section); ?></h1>
            <span>👩‍💼 <?php echo e($_SESSION['user_name']); ?></span> <!-- fetch and show name-->
        </div>

        <?php if ($actionMsg): ?>
            <div class="info-box info-box-green" style="margin-bottom:16px">✅ <?php echo e($actionMsg); ?></div>
        <?php endif; ?>

        <!--Overview -->
        <?php if ($section === 'overview'): ?>

            <div class="stat-cards-row">
                <div class="stat-card"><div class="big-number"><?php echo $totalUsers; ?></div><div class="small-label">Users</div></div>
                <div class="stat-card"><div class="big-number"><?php echo $totalLost; ?></div><div class="small-label">Lost Reports</div></div>
                <div class="stat-card green"><div class="big-number"><?php echo $totalFound; ?></div><div class="small-label">Found Posts</div></div>
                <div class="stat-card orange"><div class="big-number"><?php echo $resolved; ?></div><div class="small-label">Resolved</div></div>
                <div class="stat-card <?php echo $unreadMessages > 0 ? 'red' : ''; ?>"><!-- red if there are unread messages -->
                    <div class="big-number"><?php echo $unreadMessages; ?></div><!-- show count-->
                    <div class="small-label">Unread Messages</div>
                </div>
            </div>

            <div class="two-col-grid">
                <div class="white-box">
                    <h3>Recent Lost Reports</h3>
                    <?php foreach ($recentLost as $item): ?> <!-- loop recent lost items in above arrAy -->
                        <div class="my-item-row">
                            <div class="my-item-text">
                                <div class="item-name"><?php echo e($item['title']); ?></div>
                                <div class="item-sub"><?php echo e($item['reporter']); ?> · <?php echo e($item['location']); ?></div>
                            </div>
                            <span class="tag <?php echo $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-lost'; ?>">
                                <?php echo $item['status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="white-box">
                    <h3>Recent Found Posts</h3>
                    <?php foreach ($recentFound as $item): ?><!--for found-->
                        <div class="my-item-row">
                            <div class="my-item-text">
                                <div class="item-name"><?php echo e($item['title']); ?></div>
                                <div class="item-sub"><?php echo e($item['reporter']); ?> · <?php echo e($item['location']); ?></div>
                            </div>
                            <span class="tag <?php echo $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-found'; ?>">
                                <?php echo $item['status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <!--All Items-->
        <?php elseif ($section === 'items'): ?>

            <!--Filter -->
            <form method="GET" action="admin.php" style="display:flex;gap:8px;margin-bottom:16px">
                <input type="hidden" name="section" value="items">
                <select name="type_filter" onchange="this.form.submit()"> <!-- filter by type -->
                    <option value="all"<?php if (($_GET['type_filter'] ?? 'all') === 'all') echo 'selected'; ?>>All Types</option>
                    <option value="lost" <?php if (($_GET['type_filter'] ?? '') === 'lost') echo 'selected'; ?>>Lost Only</option>
                    <option value="found"<?php if (($_GET['type_filter'] ?? '') === 'found') echo 'selected'; ?>>Found Only</option>
                </select>
                <select name="status_filter" onchange="this.form.submit()"><!--by status -->
                    <option value="all" <?php if (($_GET['status_filter'] ?? 'all') === 'all') echo 'selected'; ?>>All Statuses</option>
                    <option value="active" <?php if (($_GET['status_filter'] ?? '') === 'active')echo 'selected'; ?>>Active</option>
                    <option value="resolved" <?php if (($_GET['status_filter'] ?? '') === 'resolved') echo 'selected'; ?>>Resolved</option>
                </select>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#6b8ab0">No items found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $i =>$item): ?> <!-- Loop through items and show in table -->
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($item['title']); ?></td>
                                <td>
                                    <span class="tag <?php echo $item['item_type'] === 'lost' ? 'tag-lost' : 'tag-found'; ?>">
                                        <?php echo $item['item_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo e($item['reporter']); ?></td> 
                                <td><?php echo e($item['location']); ?></td>
                                <td>
                                    <span class="tag <?php echo $item['status'] === 'resolved' ? 'tag-resolved' : ''; ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_item.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['item_type']; ?>"
                                       class="btn btn-small btn-white">Edit</a>

                                    <!-- Change status form -->
                                    <form method="POST" action="admin.php?section=items" style="display:inline">
                                        <input type="hidden" name="change_status" value="1">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $item['status'] === 'resolved' ? 'active' : 'resolved'; ?>">
                                        <button type="submit" class="btn btn-small btn-green">
                                            <?php echo $item['status'] === 'resolved' ? 'Reopen' : 'Resolve'; ?>
                                        </button>
                                    </form>

                                    <!-- Delete form -->
                                    <form method="POST" action="admin.php?section=items" style="display:inline"
                                          onsubmit="return confirm('Delete this item?')">
                                        <input type="hidden" name="delete_item" value="1">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                                        <button type="submit" class="btn btn-small btn-red">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <!--Messages  -->
        <?php elseif ($section === 'messages'): ?>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>From</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#6b8ab0">No messages.</td></tr>
                    <?php else: ?>
                        <?php foreach ($messages as $i => $msg): ?> <!-- Loop messages -->
                            <tr style="<?php echo !$msg['is_read'] ? 'font-weight:600' : ''; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($msg['item_title'] ?? '—'); ?> (<?php echo $msg['item_type']; ?>)</td>
                                <td><?php echo e($msg['sender_name']); ?></td>
                                <td><?php echo e($msg['sender_email']); ?></td>
                                <td><?php echo e(substr($msg['body'], 0, 60)). (strlen($msg['body']) > 60 ? '…' : ''); ?></td>
                                <td><?php echo substr($msg['created_at'], 0, 10); ?></td>
                                <td>
                                    <?php if (!$msg['is_read']): ?><!--mark read -->
                                        <form method="POST" action="admin.php?section=messages" style="display:inline">
                                            <input type="hidden" name="mark_read" value="1">
                                            <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-white">Mark Read</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="admin.php?section=messages" style="display:inline"
                                          onsubmit="return confirm('Delete this message?')">
                                        <input type="hidden" name="delete_message" value="1">
                                        <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-red">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <!-- Users -->
        <?php elseif ($section === 'users'): ?>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $user): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo e($user['name']); ?></td>
                            <td><?php echo e($user['email']); ?></td>
                            <td>
                                <span class="tag <?php echo $user['role'] === 'admin' ? 'tag-lost' : ''; ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td><?php echo substr($user['created_at'], 0, 10); ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <!-- Toggle role -->
                                    <form method="POST" action="admin.php?section=users" style="display:inline">
                                        <input type="hidden" name="set_role" value="1">
                                        <input type="hidden" name="target_user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="new_role" value="<?php echo $user['role'] === 'admin' ? 'student' : 'admin'; ?>">
                                        <button type="submit" class="btn btn-small btn-white">
                                            <?php echo $user['role'] === 'admin' ? 'Make Student' : 'Make Admin'; ?>
                                        </button>
                                    </form>
                                    <!-- Delete user -->
                                    <form method="POST" action="admin.php?section=users" style="display:inline"
                                          onsubmit="return confirm('Delete this user and all their data?')">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="target_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-red">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <em style="color:#6b8ab0;font-size:12px">You</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </div>
</div>

<footer><p>&copy; 2026 YIC Lost &amp; Found Portal | CS381 Project</p></footer>
</body>
</html>
