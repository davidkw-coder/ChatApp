<?php
require_once 'config.php';

// Require login
requireLogin();

// Get current user ID
$userId = getCurrentUserId();

// Mark notifications as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $conn->close();
    
    // Redirect to remove query string
    header('Location: notifications.php');
    exit;
}

// Get notifications
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT n.*, u.username, u.avatar
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get unread count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$unreadCount = $result->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
    <style>
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .notification-item {
            background-color: var(--bg-color);
            border-radius: var(--radius);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
        }
        
        .notification-item.unread {
            border-left: 3px solid var(--primary-color);
            background-color: rgba(79, 70, 229, 0.05);
        }
        
        .notification-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Notifications <?php if ($unreadCount > 0): ?><span class="badge">(<?php echo $unreadCount; ?> unread)</span><?php endif; ?></h2>
            
            <?php if (count($notifications) > 0): ?>
                <a href="notifications.php?mark_read=all" class="btn btn-outline btn-sm">Mark All as Read</a>
            <?php endif; ?>
        </div>
        
        <?php if (count($notifications) > 0): ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <?php if (!empty($notification['from_user_id'])): ?>
                            <img class="notification-avatar" src="<?php echo !empty($notification['avatar']) ? h($notification['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($notification['username'] ?? 'User'); ?>">
                        <?php else: ?>
                            <div class="notification-icon">📢</div>
                        <?php endif; ?>
                        
                        <div class="notification-content">
                            <div class="notification-message">
                                <?php echo h($notification['message']); ?>
                            </div>
                            <div class="notification-time">
                                <?php echo formatDate($notification['created_at']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($notification['link'])): ?>
                            <div class="notification-actions">
                                <a href="<?php echo h($notification['link']); ?>" class="btn btn-primary btn-sm">View</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>You don't have any notifications yet.</p>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

