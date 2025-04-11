<?php
/**
 * Message handler for ChatApp
 * Handles sending and retrieving messages
 */

require_once 'config.php';

// Get database connection
$conn = getDbConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        // Get messages
        getMessages($conn);
        break;
        
    case 'POST':
        // Send message
        $data = json_decode(file_get_contents('php://input'), true);
        sendMessage($conn, $data);
        break;
        
    default:
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        break;
}

// Get messages
function getMessages($conn) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Get last message ID from query string
    $lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    // Update user's last active time
    $stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Get messages newer than last_id
    $stmt = $conn->prepare("
        SELECT m.id, m.user_id, m.message, m.created_at, u.username 
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.id > ?
        ORDER BY m.created_at ASC
        LIMIT 50
    ");
    $stmt->bind_param("i", $lastId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Return messages
    sendJsonResponse([
        'success' => true,
        'messages' => $messages
    ]);
}

// Send message
function sendMessage($conn, $data) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Validate message
    if (empty($data['message'])) {
        sendJsonResponse(['success' => false, 'message' => 'Message cannot be empty'], 400);
    }
    
    $message = $conn->real_escape_string($data['message']);
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $message);
    
    if (!$stmt->execute()) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to send message: ' . $conn->error], 500);
    }
    
    // Update user's last active time
    $stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Return success
    sendJsonResponse([
        'success' => true,
        'message' => 'Message sent successfully',
        'message_id' => $conn->insert_id
    ]);
}

// Close database connection
$conn->close();
?>

