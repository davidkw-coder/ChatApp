<?php
require_once 'config.php';

// Require login
requireLogin();

// Update last active time
updateLastActive();

// Get current user data
$currentUser = getUserData();

// Get users with chat history
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN pm.sender_id = ? THEN pm.receiver_id 
            ELSE pm.sender_id 
        END as user_id,
        u.username, u.avatar, u.status, u.last_active,
        (SELECT MAX(created_at) FROM private_messages 
         WHERE (sender_id = ? AND receiver_id = user_id) OR 
               (sender_id = user_id AND receiver_id = ?)) as last_message_time,
        (SELECT COUNT(*) FROM private_messages 
         WHERE sender_id = user_id AND receiver_id = ? AND is_read = 0) as unread_count,
        (SELECT message FROM private_messages 
         WHERE ((sender_id = ? AND receiver_id = user_id) OR 
               (sender_id = user_id AND receiver_id = ?))
         ORDER BY created_at DESC LIMIT 1) as last_message
    FROM private_messages pm
    JOIN users u ON (
        CASE 
            WHEN pm.sender_id = ? THEN pm.receiver_id 
            ELSE pm.sender_id 
        END = u.id
    )
    WHERE pm.sender_id = ? OR pm.receiver_id = ?
    ORDER BY last_message_time DESC
");

$chatUsers = [];
// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt->bind_param("iiiiiiiii", $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $chatUsers[] = $row;
    }
}

// Get friends who are not in chat history
$stmt = $conn->prepare("
    SELECT u.id as user_id, u.username, u.avatar, u.status, u.last_active
    FROM users u
    JOIN friends f ON (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
    WHERE f.status = 'accepted'
    AND u.id NOT IN (
        SELECT DISTINCT 
            CASE 
                WHEN pm.sender_id = ? THEN pm.receiver_id 
                ELSE pm.sender_id 
            END as user_id
        FROM private_messages pm
        WHERE pm.sender_id = ? OR pm.receiver_id = ?
    )
    ORDER BY u.username ASC
");

$otherFriends = [];
// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt->bind_param("iiiii", $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $otherFriends[] = $row;
    }
}

// Get total unread messages
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM private_messages 
    WHERE receiver_id = ? AND is_read = 0
");

$totalUnread = 0;
// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalUnread = $result->fetch_assoc()['total'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Conversations - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="chat-list-header">
            <h2>Your Conversations <?php if ($totalUnread > 0): ?><span class="badge">(<?php echo $totalUnread; ?> unread)</span><?php endif; ?></h2>
            <a href="chat.php" class="btn btn-secondary">Public Chat</a>
        </div>
        
        <div class="chat-list-container">
            <?php if (count($chatUsers) > 0): ?>
                <div class="chat-list">
                    <?php foreach ($chatUsers as $user): ?>
                        <div class="chat-list-item" onclick="window.location.href='personal-chat.php?user=<?php echo $user['user_id']; ?>'">
                            <div class="user-avatar">
                                <img src="<?php echo !empty($user['avatar']) ? h($user['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($user['username']); ?>">
                                <span class="status-indicator <?php echo isUserOnline($user['last_active']) ? 'online' : 'offline'; ?>"></span>
                            </div>
                            <div class="chat-info">
                                <div class="chat-header">
                                    <h3><?php echo h($user['username']); ?></h3>
                                    <span class="chat-time"><?php echo formatDate($user['last_message_time']); ?></span>
                                </div>
                                <div class="chat-preview">
                                    <p><?php echo h(substr($user['last_message'], 0, 50)) . (strlen($user['last_message']) > 50 ? '...' : ''); ?></p>
                                    <?php if ($user['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $user['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($otherFriends) > 0): ?>
                <div class="friends-section">
                    <h3>Start a Conversation</h3>
                    <div class="friends-list">
                        <?php foreach ($otherFriends as $friend): ?>
                            <div class="friend-item" onclick="window.location.href='personal-chat.php?user=<?php echo $friend['user_id']; ?>'">
                                <div class="user-avatar">
                                    <img src="<?php echo !empty($friend['avatar']) ? h($friend['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($friend['username']); ?>">
                                    <span class="status-indicator <?php echo isUserOnline($friend['last_active']) ? 'online' : 'offline'; ?>"></span>
                                </div>
                                <div class="friend-info">
                                    <h3><?php echo h($friend['username']); ?></h3>
                                    <span class="status-text"><?php echo isUserOnline($friend['last_active']) ? 'Online' : 'Offline'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (count($chatUsers) === 0 && count($otherFriends) === 0): ?>
                <div class="empty-state">
                    <p>You don't have any conversations yet. Add friends to start chatting!</p>
                    <a href="friends.php" class="btn btn-primary">Find Friends</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

