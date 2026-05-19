<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin(); // Only admins can access this page

$section = $_GET['section'] ?? 'overview'; //get section from url (default overview)
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); //pdo queries to count totals 
$totalLost = $pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
$totalFound = $pdo->query("SELECT COUNT(*) FROM found_items")->fetchColumn();
$unreadMessages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$resolved = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE status='resolved') + (SELECT COUNT(*) FROM found_items WHERE status='resolved')")->fetchColumn();

$actionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Delete item
    if (isset($_POST['delete_item'])) { //if delete button is clicked sumbited form
        $delId = (int) $_POST['item_id']; //get item id and type from form
        $delType = $_POST['item_type'];
        $table = $delType === 'found' ? 'found_items' : 'lost_items';  //to decide table
        $stmt = $pdo->prepare("SELECT image_path FROM $table WHERE id = ?"); //fetch image path
        $stmt->execute([$delId]);//delete image file if exists
        $row = $stmt->fetch();
        if ($row && $row['image_path'] && file_exists($row['image_path'])) unlink($row['image_path']);//unlink = delete file
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$delId]);//delete item form db
        $actionMsg = 'Item deleted'; //msg
    }

    // Change item status
    if (isset($_POST['change_status'])) {
        $delId = (int) $_POST['item_id']; //get info from form
        $delType = $_POST['item_type'];
        $newStatus = $_POST['new_status'];
        $table = $delType === 'found' ? 'found_items' : 'lost_items';// decide table
        $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?")->execute([$newStatus, $delId]);//query to update 
        $actionMsg = 'Status is updated'; 
    }

    // Delete message
    if (isset($_POST['delete_message'])) {
        $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([(int)$_POST['msg_id']]);//delete
        $actionMsg = 'Message deleted'; //msg
    }

    // update message to read
    if (isset($_POST['mark_read'])) {
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([(int)$_POST['msg_id']]);
        $actionMsg = 'Message marked read';
    }

    // Change user role
    if (isset($_POST['set_role'])) {
        $targetId = (int) $_POST['target_user_id'];
        $newRole  = $_POST['new_role'];
        if ($targetId != $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
            $actionMsg = 'User role updated';
        }
    }

    // Delete user
    if (isset($_POST['delete_user'])) {
        $targetId = (int) $_POST['target_user_id'];
        if ($targetId != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $actionMsg = 'User deleted';
        }
    }
}

//initilizw arrays
$items = [];
$messages = [];
$users = [];

if ($section === 'items') {
    $typeFilter = $_GET['type_filter'] ?? 'all';//get from url
    $statusFilter = $_GET['status_filter'] ?? 'all';//

    if ($typeFilter !== 'found') {//if not found = lost
        $sql = "SELECT l.*, u.name AS reporter, 'lost' AS item_type FROM lost_items l JOIN users u ON u.id = l.user_id WHERE 1=1";
        //quiry to fetch all columns from lost items, Hardcodes 'lost' as a literal item_type column

        $params = []; //for prepared statement parameters
        if ($statusFilter !== 'all') { $sql .= " AND l.status = ?"; $params[] = $statusFilter; }//if filter is applied, add to query and params
        $stmt = $pdo->prepare($sql . " ORDER BY l.created_at DESC"); //order query (by date)
        $stmt->execute($params);
        $items = array_merge($items, $stmt->fetchAll());//merge results into items array
    }
    if ($typeFilter !== 'lost') { //for found
        $sql = "SELECT f.*, u.name AS reporter, 'found' AS item_type FROM found_items f JOIN users u ON u.id = f.user_id WHERE 1=1";
        $params = [];
        if ($statusFilter !== 'all') { $sql .= " AND f.status = ?"; $params[] = $statusFilter; }
        $stmt = $pdo->prepare($sql . " ORDER BY f.created_at DESC");
        $stmt->execute($params);
        $items = array_merge($items, $stmt->fetchAll());
    }
    usort($items, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));//final sort by date after merge
}

if ($section === 'messages') {//if current section is message - from url
    $messages = $pdo->query(
        "SELECT m.*,
             CASE WHEN m.item_type='lost' THEN l.title ELSE f.title END AS item_title
         FROM messages m
         LEFT JOIN lost_items  l ON l.id = m.item_id AND m.item_type = 'lost'
         LEFT JOIN found_items f ON f.id = m.item_id AND m.item_type = 'found'
         ORDER BY m.created_at DESC"
    )->fetchAll(); //select all messages,if item lost title from lost items.. 
    //join with table based on item type to get item title then order it
}

if ($section === 'users') {//fetch all users in section user
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

$recentLost = $pdo->query("SELECT l.*, u.name AS reporter FROM lost_items  l JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 5")->fetchAll();
$recentFound = $pdo->query("SELECT f.*, u.name AS reporter FROM found_items f JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC LIMIT 5")->fetchAll();
//select most rwcwnt (5) items from each
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
            <a href="admin.php?section=overview" class="admin-menu-item <?php echo $section === 'overview' ? 'active' : ''; ?>">📊 Overview</a>
            <a href="admin.php?section=items" class="admin-menu-item <?php echo $section === 'items' ? 'active' : ''; ?>">📦 All Items</a>
            <a href="admin.php?section=messages" class="admin-menu-item <?php echo $section === 'messages' ? 'active' : ''; ?>"> <!-- php here checks which active -->
                ✉️ Messages
                <?php if ($unreadMessages > 0): ?> <!-- if unredd make it red -->
                    <span style="background:#ef4444;color:#fff;border-radius:99px;padding:1px 7px;font-size:11px;margin-left:4px"><?php echo $unreadMessages; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin.php?section=users" class="admin-menu-item <?php echo $section === 'users' ? 'active' : ''; ?>">👥 Users</a>
            <a href="index.php" class="admin-menu-item">🏠 View Portal</a> <!-- redirect-->
            <a href="logout.php" class="admin-menu-item">↩ Logout</a>
        </nav>
    </div>

    <div class="admin-main-area">

        <div class="admin-top-strip">
            <h1><?php echo ucfirst($section); ?></h1> <!-- cpitalizes first letter of section-->
            <span>👩‍💼 <?php echo e($_SESSION['user_name']); ?></span><!-- display username from session -->
        </div> <!-- e() is a helper function runa htmlspecialchars to prevent xss -->

        <?php if ($actionMsg): ?> <!--action message style -->
            <div class="info-box info-box-green" style="margin-bottom:16px">✅ <?php echo e($actionMsg); ?></div>
        <?php endif; ?>

        <!--Overview-->
        <?php if ($section === 'overview'): ?>

            <div class="stat-cards-row"> <!--show totals from count query up -->
                <div class="stat-card"><div class="big-number"><?php echo $totalUsers; ?></div><div class="small-label">Users</div></div>
                <div class="stat-card"><div class="big-number"><?php echo $totalLost; ?></div><div class="small-label">Lost Reports</div></div>
                <div class="stat-card green"><div class="big-number"><?php echo $totalFound; ?></div><div class="small-label">Found Posts</div></div>
                <div class="stat-card orange"><div class="big-number"><?php echo $resolved; ?></div><div class="small-label">Resolved</div></div>
                <div class="stat-card <?php echo $unreadMessages > 0 ? 'red' : ''; ?>"><!-- if  any unread make it red -->
                    <div class="big-number"><?php echo $unreadMessages; ?></div><!-- show number of unread -->
                    <div class="small-label">Unread Messages</div>
                </div>
            </div>

            <div class="two-col-grid">
                <div class="white-box">
                    <h3>Recent Lost Reports</h3>
                    <?php foreach ($recentLost as $item): ?> <!-- loop through recent lost items -->
                        <div class="my-item-row">
                            <div class="my-item-text">
                                <div class="item-name"><?php echo e($item['title']); ?></div>
                                <div class="item-sub"><?php echo e($item['reporter']); ?> · <?php echo e($item['location']); ?></div>
                            </div>
                            <span class="tag <?php echo $item['status'] === 'resolved' ? 'tag-resolved' : 'tag-lost'; ?>">
                                <?php echo $item['status']; ?> <!-- show status with tag-->
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="white-box"><!--same-->
                    <h3>Recent Found Posts</h3>
                    <?php foreach ($recentFound as $item): ?>
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

            <form method="GET" action="admin.php" style="display:flex;gap:8px;margin-bottom:16px">
                <input type="hidden" name="section" value="items">
                <select name="type_filter" onchange="this.form.submit()"><!--submit on change -->
                    <option value="all" <?php if (($_GET['type_filter'] ?? 'all') === 'all')  echo 'selected'; ?>>All Types</option> <!--get from url ,keep selected-->
                    <option value="lost" <?php if (($_GET['type_filter'] ?? '') === 'lost') echo 'selected'; ?>>Lost Only</option>
                    <option value="found" <?php if (($_GET['type_filter'] ?? '') === 'found') echo 'selected'; ?>>Found Only</option>
                </select>
                <select name="status_filter" onchange="this.form.submit()">
                    <option value="all" <?php if (($_GET['status_filter'] ?? 'all') === 'all') echo 'selected'; ?>>All Statuses</option>
                    <option value="active" <?php if (($_GET['status_filter'] ?? '') === 'active') echo 'selected'; ?>>Active</option>
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
                    <?php if (empty($items)): ?> <!--condition if no items-->
                        <tr><td colspan="7" style="text-align:center;color:#6b8ab0">No items found</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $i => $item): ?><!-- loop through items-->
                            <tr>
                                <td><?php echo $i + 1; ?></td> <!-- show index from 1- not 0-->
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
                                        <?php csrfField(); ?> <!--When the form loads a random token is generated and stored in the session-->
                                        <!-- this prevent CSRF attacks-->
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
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="delete_item" value="1">
                                        <input type="hidden" name="item_id"   value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                                        <button type="submit" class="btn btn-small btn-red">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <!--Messages-->
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
                        <?php foreach ($messages as $i => $msg): ?> <!-- loop through messages-->
                            <tr style="<?php echo !$msg['is_read'] ? 'font-weight:600' : ''; ?>"> <!-- if not read make it bold -->
                                <td><?php echo $i + 1; ?></td> 
                                <td><?php echo e($msg['item_title'] ?? '—'); ?> (<?php echo $msg['item_type']; ?>)</td> 
                                <td><?php echo e($msg['sender_name']); ?></td>
                                <td><?php echo e($msg['sender_email']); ?></td>
                                <td><?php echo e(substr($msg['body'], 0, 60)) . (strlen($msg['body']) > 60 ? '…' : ''); ?></td>
                                <!-- substr help display only first 60 char of ms, and adds sots -->
                                <td><?php echo substr($msg['created_at'], 0, 10); ?></td> <!-- dat no time -->
                                <td>
                                    <?php if (!$msg['is_read']): ?>
                                        <form method="POST" action="admin.php?section=messages" style="display:inline">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="mark_read" value="1">
                                            <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-white">Mark Read</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="admin.php?section=messages" style="display:inline"
                                          onsubmit="return confirm('Delete this message?')">
                                        <?php csrfField(); ?>
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

        <!--Users-->
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
                                <?php if ($user['id'] != $_SESSION['user_id']): ?> <!-- prevent admin from changing their role/delete-->
                                    <!-- Toggle role -->
                                    <form method="POST" action="admin.php?section=users" style="display:inline">
                                        <?php csrfField(); ?>
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
                                        <?php csrfField(); ?>
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