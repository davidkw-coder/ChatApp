<?php
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    
    // Update user status to offline
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE users SET status = 'offline' WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $conn->close();
    
    // Clear session
    session_unset();
    session_destroy();
}

// Redirect to login page
setFlashMessage('info', 'You have been logged out');
header('Location: login.php');
exit;
?>

