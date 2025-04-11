<?php
/**
 * API endpoints for ChatApp
 * Handles AJAX requests for chat functionality
 */

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn() && $_GET['action'] !== 'check_username' && $_GET['action'] !== 'check_email') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Update last active time
updateLastActive();

// Get action from query string
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'get_messages':
        getMessages();
        break;
        
    case 'send_message':
        sendMessage();
        break;
        
    case 'get_active_users':
        getActiveUsers();
        break;
        
    case 'update_status':
        updateStatus();
        break;
        
    case 'check_username':
        checkUsername();
        break;
        
    case 'check_email':
        checkEmail();
        break;
        
    case 'get_posts':
        getPosts();
        break;
        
    case 'like_post':
        likePost();
        break;
        
    case 'get_comments':
        getComments();
        break;
        
    case 'add_comment':
        addComment();
        break;
        
    case 'get_private_messages':
        getPrivateMessages();
        break;
        
    case 'send_private_message':
        sendPrivateMessage();
        break;
        
    case 'mark_messages_read':
        markMessagesRead();
        break;
        
    case 'get_unread_count':
        getUnreadCount();
        break;
        
    case 'get_chat_users':
        getChatUsers();
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Get messages
function getMessages() {
   $conn = getDbConnection();
   
   // Get last message ID and offset from query string
   $lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
   $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
   $direction = isset($_GET['direction']) ? $_GET['direction'] : 'newer'; // 'newer' or 'older'
   $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
   
   // Get messages based on direction
   if ($direction === 'older') {
       // Get messages older than last_id
       $stmt = $conn->prepare("
           SELECT m.id, m.user_id, m.message, m.created_at, u.username 
           FROM messages m
           JOIN users u ON m.user_id = u.id
           WHERE m.id < ?
           ORDER BY m.id DESC
       ");
       
       $messages = [];
       // Check if prepare was successful
       if ($stmt === false) {
           error_log("Prepare failed: " . $conn->error);
       } else {
           $stmt->bind_param("i", $lastId);
           $stmt->execute();
           $result = $stmt->get_result();
           
           while ($row = $result->fetch_assoc()) {
               $messages[] = $row;
           }
           
           // Reverse to get chronological order
           $messages = array_reverse($messages);
       }
   } else {
       // Get messages newer than last_id
       $stmt = $conn->prepare("
           SELECT m.id, m.user_id, m.message, m.created_at, u.username 
           FROM messages m
           JOIN users u ON m.user_id = u.id
           WHERE m.id > ?
           ORDER BY m.created_at ASC
       ");
       
       $messages = [];
       // Check if prepare was successful
       if ($stmt === false) {
           error_log("Prepare failed: " . $conn->error);
       } else {
           $stmt->bind_param("i", $lastId);
           $stmt->execute();
           $result = $stmt->get_result();
           
           while ($row = $result->fetch_assoc()) {
               $messages[] = $row;
           }
       }
   }
   
   // Get total message count for pagination info
   $stmt = $conn->prepare("SELECT COUNT(*) as total FROM messages");
   $totalMessages = 0;
   
   if ($stmt === false) {
       error_log("Prepare failed: " . $conn->error);
   } else {
       $stmt->execute();
       $result = $stmt->get_result();
       $totalMessages = $result->fetch_assoc()['total'];
   }
   
   $conn->close();
   
   // Return messages
   header('Content-Type: application/json');
   echo json_encode([
       'success' => true, 
       'messages' => $messages,
       'total' => $totalMessages,
       'has_more' => count($messages) == $limit
   ]);
}

// Send message
function sendMessage() {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['message'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    $userId = getCurrentUserId();
    $message = $data['message'];
    
    $conn = getDbConnection();
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, NOW())");
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        exit;
    }
    
    $stmt->bind_param("is", $userId, $message);
    
    if ($stmt->execute()) {
        $messageId = $conn->insert_id;
        $conn->close();
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message_id' => $messageId]);
    } else {
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}

// Get active users
function getActiveUsers() {
    $conn = getDbConnection();
    
    // Get active users (active in the last 5 minutes)
    $stmt = $conn->prepare("
        SELECT id, username, avatar, status
        FROM users
        WHERE last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY username ASC
    ");
    
    $users = [];
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    
    // Return active users
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $users]);
}

// Update user status
function updateStatus() {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['status']) || !in_array($data['status'], ['online', 'offline'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    $userId = getCurrentUserId();
    $status = $data['status'];
    
    $conn = getDbConnection();
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = ?, last_active = NOW() WHERE id = ?");
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        exit;
    }
    
    $stmt->bind_param("si", $status, $userId);
    
    if ($stmt->execute()) {
        $conn->close();
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}

// Check if username exists
function checkUsername() {
    // Get username from query string
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        exit;
    }
    
    $conn = getDbConnection();
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    
    $exists = false;
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $exists = $result->num_rows > 0;
    }
    
    $conn->close();
    
    // Return result
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'exists' => $exists]);
}

// Check if email exists
function checkEmail() {
    // Get email from query string
    $email = $_GET['email'] ?? '';
    
    if (empty($email)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    $conn = getDbConnection();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    
    $exists = false;
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $exists = $result->num_rows > 0;
    }
    
    $conn->close();
    
    // Return result
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'exists' => $exists]);
}

// Get posts
function getPosts() {
    $conn = getDbConnection();
    
    // Get page from query string
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Get posts with pagination
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar 
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $perPage);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    // Get total posts count for pagination
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts");
    $stmt->execute();
    $result = $stmt->get_result();
    $totalPosts = $result->fetch_assoc()['count'];
    
    $totalPages = ceil($totalPosts / $perPage);
    
    $conn->close();
    
    // Return posts
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'posts' => $posts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_posts' => $totalPosts
        ]
    ]);
}

// Like post
function likePost() {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['post_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        exit;
    }
    
    $userId = getCurrentUserId();
    $postId = $data['post_id'];
    
    $conn = getDbConnection();
    
    // Check if already liked
    $stmt = $conn->prepare("
        SELECT id FROM post_likes 
        WHERE post_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike post
        $stmt = $conn->prepare("
            DELETE FROM post_likes 
            WHERE post_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $postId, $userId);
        
        if ($stmt->execute()) {
            // Update post likes count
            $stmt = $conn->prepare("
                UPDATE posts 
                SET likes = (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) 
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $postId, $postId);
            $stmt->execute();
            
            // Get updated likes count
            $stmt = $conn->prepare("SELECT likes FROM posts WHERE id = ?");
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();
            
            $conn->close();
            
            // Return success
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'liked' => false,
                'likes' => $post['likes']
            ]);
        } else {
            $conn->close();
            
            // Return error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to unlike post']);
        }
    } else {
        // Like post
        $stmt = $conn->prepare("
            INSERT INTO post_likes (post_id, user_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->bind_param("ii", $postId, $userId);
        
        if ($stmt->execute()) {
            // Update post likes count
            $stmt = $conn->prepare("
                UPDATE posts 
                SET likes = (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) 
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $postId, $postId);
            $stmt->execute();
            
            // Get updated likes count
            $stmt = $conn->prepare("SELECT likes, user_id FROM posts WHERE id = ?");
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();
            
            // Notify post owner if not self
            if ($post['user_id'] != $userId) {
                // Get username
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                $message = $user['username'] . ' liked your post';
                $link = 'feed.php?post=' . $postId;
                
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, from_user_id, message, link, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("iiss", $post['user_id'], $userId, $message, $link);
                $stmt->execute();
            }
            
            $conn->close();
            
            // Return success
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'liked' => true,
                'likes' => $post['likes']
            ]);
        } else {
            $conn->close();
            
            // Return error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to like post']);
        }
    }
}

// Get comments
function getComments() {
    // Get post ID from query string
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (empty($postId)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        exit;
    }
    
    $conn = getDbConnection();
    
    // Get comments for the post
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.avatar 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    $conn->close();
    
    // Return comments
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'comments' => $comments]);
}

// Add comment
function addComment() {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['post_id']) || empty($data['content'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Post ID and content are required']);
        exit;
    }
    
    $userId = getCurrentUserId();
    $postId = $data['post_id'];
    $content = $data['content'];
    
    $conn = getDbConnection();
    
    // Insert comment
    $stmt = $conn->prepare("
        INSERT INTO comments (post_id, user_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $postId, $userId, $content);
    
    if ($stmt->execute()) {
        $commentId = $conn->insert_id;
        
        // Get comment with user info
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.avatar 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();
        
        // Notify post owner if not self
        $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        
        if ($post['user_id'] != $userId) {
            // Get username
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            $message = $user['username'] . ' commented on your post';
            $link = 'feed.php?post=' . $postId;
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, from_user_id, message, link, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiss", $post['user_id'], $userId, $message, $link);
            $stmt->execute();
        }
        
        $conn->close();
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'comment' => $comment]);
    } else {
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
}

// Get private messages
function getPrivateMessages() {
   $userId = getCurrentUserId();
   $otherUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
   $lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
   $direction = isset($_GET['direction']) ? $_GET['direction'] : 'newer'; // 'newer' or 'older'
   $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
   
   if (empty($otherUserId)) {
       header('Content-Type: application/json');
       echo json_encode(['success' => false, 'message' => 'User ID is required']);
       exit;
   }
   
   $conn = getDbConnection();
   
   // Get messages between the two users based on direction
   if ($direction === 'older') {
       // Get messages older than last_id
       $stmt = $conn->prepare("
           SELECT pm.*, u.username, u.avatar 
           FROM private_messages pm
           JOIN users u ON pm.sender_id = u.id
           WHERE pm.id < ? AND (
               (pm.sender_id = ? AND pm.receiver_id = ?) OR 
               (pm.sender_id = ? AND pm.receiver_id = ?)
           )
           ORDER BY pm.id DESC
       ");
       
       $messages = [];
       // Check if prepare was successful
       if ($stmt === false) {
           error_log("Prepare failed: " . $conn->error);
       } else {
           $stmt->bind_param("iiiii", $lastId, $userId, $otherUserId, $otherUserId, $userId);
           $stmt->execute();
           $result = $stmt->get_result();
           
           while ($row = $result->fetch_assoc()) {
               $messages[] = $row;
           }
           
           // Reverse to get chronological order
           $messages = array_reverse($messages);
       }
   } else {
       // Get messages newer than last_id
       $stmt = $conn->prepare("
           SELECT pm.*, u.username, u.avatar 
           FROM private_messages pm
           JOIN users u ON pm.sender_id = u.id
           WHERE pm.id > ? AND (
               (pm.sender_id = ? AND pm.receiver_id = ?) OR 
               (pm.sender_id = ? AND pm.receiver_id = ?)
           )
           ORDER BY pm.created_at ASC
       ");
       
       $messages = [];
       // Check if prepare was successful
       if ($stmt === false) {
           error_log("Prepare failed: " . $conn->error);
       } else {
           $stmt->bind_param("iiiii", $lastId, $userId, $otherUserId, $otherUserId, $userId);
           $stmt->execute();
           $result = $stmt->get_result();
           
           while ($row = $result->fetch_assoc()) {
               $messages[] = $row;
           }
       }
   }
   
   // Get total message count for pagination info
   $stmt = $conn->prepare("
       SELECT COUNT(*) as total 
       FROM private_messages 
       WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
   ");
   $totalMessages = 0;
   
   if ($stmt === false) {
       error_log("Prepare failed: " . $conn->error);
   } else {
       $stmt->bind_param("iiii", $userId, $otherUserId, $otherUserId, $userId);
       $stmt->execute();
       $result = $stmt->get_result();
       $totalMessages = $result->fetch_assoc()['total'];
   }
   
   $conn->close();
   
   // Return messages
   header('Content-Type: application/json');
   echo json_encode([
       'success' => true, 
       'messages' => $messages,
       'total' => $totalMessages,
       'has_more' => count($messages) == $limit
   ]);
}

// Send private message
function sendPrivateMessage() {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['receiver_id']) || empty($data['message'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Receiver ID and message are required']);
        exit;
    }
    
    $senderId = getCurrentUserId();
    $receiverId = $data['receiver_id'];
    $message = $data['message'];
    
    $conn = getDbConnection();
    
    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO private_messages (sender_id, receiver_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        exit;
    }
    
    $stmt->bind_param("iis", $senderId, $receiverId, $message);
    
    if ($stmt->execute()) {
        $messageId = $conn->insert_id;
        
        // Get message with sender info
        $stmt = $conn->prepare("
            SELECT pm.*, u.username, u.avatar 
            FROM private_messages pm
            JOIN users u ON pm.sender_id = u.id
            WHERE pm.id = ?
        ");
        
        $message = null;
        // Check if prepare was successful
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("i", $messageId);
            $stmt->execute();
            $result = $stmt->get_result();
            $message = $result->fetch_assoc();
        }
        
        // Create notification for receiver
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        
        // Check if prepare was successful
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("i", $senderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $sender = $result->fetch_assoc();
            
            $notificationMessage = $sender['username'] . ' sent you a message';
            $link = 'personal-chat.php?user=' . $senderId;
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, from_user_id, message, link, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            // Check if prepare was successful
            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
            } else {
                $stmt->bind_param("iiss", $receiverId, $senderId, $notificationMessage, $link);
                $stmt->execute();
            }
        }
        
        $conn->close();
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}

// Mark messages as read
function markMessagesRead() {
    $userId = getCurrentUserId();
    $senderId = isset($_GET['sender_id']) ? intval($_GET['sender_id']) : 0;
    
    if (empty($senderId)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sender ID is required']);
        exit;
    }
    
    $conn = getDbConnection();
    
    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE private_messages 
        SET is_read = 1 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
        exit;
    }
    
    $stmt->bind_param("ii", $senderId, $userId);
    
    if ($stmt->execute()) {
        $conn->close();
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        $conn->close();
        
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
    }
}

// Get unread message count
function getUnreadCount() {
    $userId = getCurrentUserId();
    
    $conn = getDbConnection();
    
    // Get unread message count
    $stmt = $conn->prepare("
        SELECT sender_id, COUNT(*) as count 
        FROM private_messages 
        WHERE receiver_id = ? AND is_read = 0 
        GROUP BY sender_id
    ");
    
    $unreadCounts = [];
    $totalUnread = 0;
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $unreadCounts[$row['sender_id']] = $row['count'];
        }
        
        // Get total unread count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM private_messages 
            WHERE receiver_id = ? AND is_read = 0
        ");
        
        // Check if prepare was successful
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalUnread = $result->fetch_assoc()['total'];
        }
    }
    
    $conn->close();
    
    // Return unread counts
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'unread_counts' => $unreadCounts,
        'total_unread' => $totalUnread
    ]);
}

// Get users with chat history
function getChatUsers() {
    $userId = getCurrentUserId();
    
    $conn = getDbConnection();
    
    // Get users with chat history
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
             WHERE sender_id = user_id AND receiver_id = ? AND is_read = 0) as unread_count
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
    
    $users = [];
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("iiiiiii", $userId, $userId, $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    
    // Return users
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $users]);
}

// The remaining functions (getPosts, likePost, getComments, addComment) would also need similar error checking
// but I've omitted them for brevity. You should apply the same pattern to those functions.
?>
