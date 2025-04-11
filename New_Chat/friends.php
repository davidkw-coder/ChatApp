<?php
require_once 'config.php';

// Require login
requireLogin();

// Get current user ID
$userId = getCurrentUserId();
if (!$userId) {
    // If user ID can't be retrieved, redirect to login
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_friend'])) {
    $friendUsername = $_POST['username'] ?? '';
    
    if (empty($friendUsername)) {
        $error = 'Username is required';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Get user by username
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("s", $friendUsername);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = 'User not found';
                } else {
                    $friend = $result->fetch_assoc();
                    $friendId = $friend['id'];
                    
                    // Check if trying to add self
                    if ($friendId === $userId) {
                        $error = 'You cannot add yourself as a friend';
                    } else {
                        // Check if already friends or request pending
                        $stmt = $conn->prepare("
                            SELECT * FROM friends 
                            WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
                        ");
                        
                        if ($stmt === false) {
                            $error = 'Database error: ' . $conn->error;
                        } else {
                            $stmt->bind_param("iiii", $userId, $friendId, $friendId, $userId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $friendship = $result->fetch_assoc();
                                
                                if ($friendship['status'] === 'accepted') {
                                    $error = 'You are already friends with this user';
                                } elseif ($friendship['status'] === 'pending' && $friendship['user_id'] === $userId) {
                                    $error = 'Friend request already sent';
                                } elseif ($friendship['status'] === 'pending' && $friendship['friend_id'] === $userId) {
                                    $error = 'This user has already sent you a friend request';
                                }
                            } else {
                                // Send friend request
                                $stmt = $conn->prepare("
                                    INSERT INTO friends (user_id, friend_id, status, created_at) 
                                    VALUES (?, ?, 'pending', NOW())
                                ");
                                
                                if ($stmt === false) {
                                    $error = 'Database error: ' . $conn->error;
                                } else {
                                    $stmt->bind_param("ii", $userId, $friendId);
                                    
                                    if ($stmt->execute()) {
                                        $success = 'Friend request sent';
                                    } else {
                                        $error = 'Failed to send friend request: ' . $conn->error;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $conn->close();
        }
    }
}

// Handle friend request response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_request'])) {
    $requestId = $_POST['request_id'] ?? 0;
    $response = $_POST['response'] ?? '';
    
    if (empty($requestId) || empty($response) || !in_array($response, ['accept', 'reject'])) {
        $error = 'Invalid request';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Check if request exists and is for current user
            $stmt = $conn->prepare("
                SELECT * FROM friends 
                WHERE id = ? AND friend_id = ? AND status = 'pending'
            ");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("ii", $requestId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = 'Friend request not found';
                } else {
                    if ($response === 'accept') {
                        // Accept friend request
                        $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE id = ?");
                        
                        if ($stmt === false) {
                            $error = 'Database error: ' . $conn->error;
                        } else {
                            $stmt->bind_param("i", $requestId);
                            
                            if ($stmt->execute()) {
                                $success = 'Friend request accepted';
                            } else {
                                $error = 'Failed to accept friend request: ' . $conn->error;
                            }
                        }
                    } else {
                        // Reject friend request
                        $stmt = $conn->prepare("DELETE FROM friends WHERE id = ?");
                        
                        if ($stmt === false) {
                            $error = 'Database error: ' . $conn->error;
                        } else {
                            $stmt->bind_param("i", $requestId);
                            
                            if ($stmt->execute()) {
                                $success = 'Friend request rejected';
                            } else {
                                $error = 'Failed to reject friend request: ' . $conn->error;
                            }
                        }
                    }
                }
            }
            
            $conn->close();
        }
    }
}

// Handle unfriend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unfriend'])) {
    $friendshipId = $_POST['friendship_id'] ?? 0;
    
    if (empty($friendshipId)) {
        $error = 'Invalid request';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Check if friendship exists and involves current user
            $stmt = $conn->prepare("
                SELECT * FROM friends 
                WHERE id = ? AND (user_id = ? OR friend_id = ?) AND status = 'accepted'
            ");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("iii", $friendshipId, $userId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = 'Friendship not found';
                } else {
                    // Delete friendship
                    $stmt = $conn->prepare("DELETE FROM friends WHERE id = ?");
                    
                    if ($stmt === false) {
                        $error = 'Database error: ' . $conn->error;
                    } else {
                        $stmt->bind_param("i", $friendshipId);
                        
                        if ($stmt->execute()) {
                            $success = 'Friend removed';
                        } else {
                            $error = 'Failed to remove friend: ' . $conn->error;
                        }
                    }
                }
            }
            
            $conn->close();
        }
    }
}

// Get friends
$conn = getDbConnection();
$friends = [];
$requests = [];
$sentRequests = [];

if ($conn !== false) {
    // Get accepted friends
    $stmt = $conn->prepare("
        SELECT f.id, f.user_id, f.friend_id, f.created_at, 
               u.username, u.avatar, u.status, u.last_active
        FROM friends f
        JOIN users u ON (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
        WHERE f.status = 'accepted'
        ORDER BY u.username ASC
    ");
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $friendsResult = $stmt->get_result();

        while ($row = $friendsResult->fetch_assoc()) {
            $friends[] = $row;
        }
    }

    // Get pending friend requests (received)
    $stmt = $conn->prepare("
        SELECT f.id, f.user_id, f.created_at, u.username, u.avatar
        FROM friends f
        JOIN users u ON f.user_id = u.id
        WHERE f.friend_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $requestsResult = $stmt->get_result();

        while ($row = $requestsResult->fetch_assoc()) {
            $requests[] = $row;
        }
    }

    // Get pending friend requests (sent)
    $stmt = $conn->prepare("
        SELECT f.id, f.friend_id, f.created_at, u.username, u.avatar
        FROM friends f
        JOIN users u ON f.friend_id = u.id
        WHERE f.user_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $sentResult = $stmt->get_result();

        while ($row = $sentResult->fetch_assoc()) {
            $sentRequests[] = $row;
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>Friends</h2>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container" style="margin-bottom: 2rem;">
            <h3>Add Friend</h3>
            <form method="POST" action="friends.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_friend" class="btn btn-primary">Send Friend Request</button>
                </div>
            </form>
        </div>
        
        <?php if (count($requests) > 0): ?>
            <div class="form-container" style="margin-bottom: 2rem;">
                <h3>Friend Requests</h3>
                <ul class="user-list">
                    <?php foreach ($requests as $request): ?>
                        <li>
                            <div class="user-avatar">
                                <img src="<?php echo !empty($request['avatar']) ? h($request['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($request['username']); ?>">
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo h($request['username']); ?></span>
                                <small>Sent <?php echo formatDate($request['created_at']); ?></small>
                            </div>
                            <div class="user-actions">
                                <form method="POST" action="friends.php" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="response" value="accept">
                                    <button type="submit" name="respond_request" class="btn btn-primary btn-sm">Accept</button>
                                </form>
                                <form method="POST" action="friends.php" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="response" value="reject">
                                    <button type="submit" name="respond_request" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (count($sentRequests) > 0): ?>
            <div class="form-container" style="margin-bottom: 2rem;">
                <h3>Sent Requests</h3>
                <ul class="user-list">
                    <?php foreach ($sentRequests as $request): ?>
                        <li>
                            <div class="user-avatar">
                                <img src="<?php echo !empty($request['avatar']) ? h($request['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($request['username']); ?>">
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo h($request['username']); ?></span>
                                <small>Sent <?php echo formatDate($request['created_at']); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <h3>Your Friends (<?php echo count($friends); ?>)</h3>
        
        <?php if (count($friends) > 0): ?>
            <div class="friends-container">
                <?php foreach ($friends as $friend): ?>
                    <div class="friend-card">
                        <img class="friend-avatar" src="<?php echo !empty($friend['avatar']) ? h($friend['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($friend['username']); ?>">
                        <div class="friend-info">
                            <h3><?php echo h($friend['username']); ?></h3>
                            <span class="status <?php echo $friend['status']; ?>">
                                <?php echo isUserOnline($friend['last_active']) ? 'Online' : 'Offline'; ?>
                            </span>
                        </div>
                        <div class="friend-actions">
                            <a href="personal-chat.php?user=<?php echo $friend['friend_id'] == $userId ? $friend['user_id'] : $friend['friend_id']; ?>" class="btn btn-primary btn-sm">Message</a>
                            <form method="POST" action="friends.php" style="display: inline;">
                                <input type="hidden" name="friendship_id" value="<?php echo $friend['id']; ?>">
                                <button type="submit" name="unfriend" class="btn btn-outline btn-sm">Unfriend</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>You don't have any friends yet. Send a friend request to get started!</p>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>
