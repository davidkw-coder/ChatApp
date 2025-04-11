<?php
require_once 'config.php';

// Require login
requireLogin();

// Update last active time
updateLastActive();

// Get current user data
$currentUser = getUserData();
if (!$currentUser) {
    // If user data can't be retrieved, redirect to login
    header('Location: login.php');
    exit;
}

// Get other user ID from query string
$otherUserId = isset($_GET['user']) ? intval($_GET['user']) : 0;

// If no user ID provided, redirect to chat list
if (empty($otherUserId)) {
    header('Location: chat-list.php');
    exit;
}

// Get other user data
$conn = getDbConnection();
if ($conn === false) {
    header('Location: chat-list.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, username, email, bio, avatar, status, last_active FROM users WHERE id = ?");

// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    $conn->close();
    header('Location: chat-list.php');
    exit;
}

$stmt->bind_param("i", $otherUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found, redirect to chat list
    $conn->close();
    header('Location: chat-list.php');
    exit;
}

$otherUser = $result->fetch_assoc();

// Mark messages from this user as read
$stmt = $conn->prepare("
    UPDATE private_messages 
    SET is_read = 1 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
");

// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt->bind_param("ii", $otherUserId, $currentUser['id']);
    $stmt->execute();
}

// Get recent messages
$stmt = $conn->prepare("
    SELECT pm.*, u.username, u.avatar 
    FROM private_messages pm
    JOIN users u ON pm.sender_id = u.id
    WHERE (pm.sender_id = ? AND pm.receiver_id = ?) OR 
          (pm.sender_id = ? AND pm.receiver_id = ?)
    ORDER BY pm.created_at DESC
    LIMIT 50
");

$messages = [];
// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt->bind_param("iiii", $currentUser['id'], $otherUserId, $otherUserId, $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Reverse messages to show oldest first
$messages = array_reverse($messages);

// Get users with chat history
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN pm.sender_id = ? THEN pm.receiver_id 
            ELSE pm.sender_id 
        END as user_id,
        u.username, u.avatar, u.status, u.last_active,
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
    ORDER BY (
        SELECT MAX(created_at) FROM private_messages 
        WHERE (sender_id = ? AND receiver_id = user_id) OR 
              (sender_id = user_id AND receiver_id = ?)
    ) DESC
");

$chatUsers = [];
// Check if prepare was successful
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt->bind_param("iiiiiii", $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $chatUsers[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo h($otherUser['username']); ?> - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
    <link rel="stylesheet" href="css/chat.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="chat-wrapper">
    <div class="chat-container" style="width: 1200px;">
            <aside class="sidebar">
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo !empty($currentUser['avatar']) ? h($currentUser['avatar']) : 'uploads/default-avatar.png'; ?>" alt="Your avatar">
                    </div>
                    <div class="user-info">
                        <h3><?php echo h($currentUser['username']); ?></h3>
                        <span class="status online">Online</span>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h4>Your Conversations</h4>
                    <ul id="chat-users" class="user-list">
                        <?php if (count($chatUsers) > 0): ?>
                            <?php foreach ($chatUsers as $user): ?>
                                <li class="<?php echo $user['user_id'] == $otherUserId ? 'active' : ''; ?>" onclick="window.location.href='personal-chat.php?user=<?php echo $user['user_id']; ?>'">
                                    <div class="user-avatar">
                                        <img src="<?php echo !empty($user['avatar']) ? h($user['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($user['username']); ?>">
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name"><?php echo h($user['username']); ?></span>
                                        <span class="status <?php echo $user['status']; ?>"><?php echo isUserOnline($user['last_active']) ? 'Online' : 'Offline'; ?></span>
                                    </div>
                                    <?php if ($user['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $user['unread_count']; ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="loading">No conversations yet</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="sidebar-actions">
                    <a href="chat.php" class="btn btn-secondary btn-sm">Public Chat</a>
                    <a href="friends.php" class="btn btn-outline btn-sm">Friends</a>
                </div>
            </aside>
            
            <main class="chat-main">
                <div class="chat-header">
                    <div class="user-info">
                        <img src="<?php echo !empty($otherUser['avatar']) ? h($otherUser['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($otherUser['username']); ?>" class="user-avatar">
                        <div>
                            <h2><?php echo h($otherUser['username']); ?></h2>
                            <span class="status <?php echo $otherUser['status']; ?>"><?php echo isUserOnline($otherUser['last_active']) ? 'Online' : 'Last seen ' . formatDate($otherUser['last_active']); ?></span>
                        </div>
                    </div>
                    <button id="toggle-sidebar" class="btn btn-outline btn-sm d-md-none">Users</button>
                </div>
                
                <div class="chat-content">
                    <div id="load-more-container" class="load-more-container">
                        <button id="load-more-btn" class="btn btn-primary btn-sm">Load Older Messages</button>
                    </div>
                    
                    <div id="chat-messages" class="chat-messages">
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message-bubble <?php echo $message['sender_id'] == $currentUser['id'] ? 'sent' : 'received'; ?>" data-message-id="<?php echo $message['id']; ?>">
                                    <div class="message-content"><?php echo h($message['message']); ?></div>
                                    <div class="message-info">
                                        <span class="message-time"><?php echo formatDate($message['created_at']); ?></span>
                                        <?php if ($message['sender_id'] == $currentUser['id'] && $message['is_read']): ?>
                                            <span class="message-status">Read</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="message-center">
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chat-input-container">
                    <form id="message-form">
                        <input type="hidden" id="receiver-id" value="<?php echo $otherUser['id']; ?>">
                        <input type="text" id="message-input" placeholder="Type your message..." autocomplete="off">
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const messageForm = document.getElementById('message-form');
            const messageInput = document.getElementById('message-input');
            const receiverId = document.getElementById('receiver-id').value;
            const toggleSidebarBtn = document.getElementById('toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const loadMoreBtn = document.getElementById('load-more-btn');
            const loadMoreContainer = document.getElementById('load-more-container');
            
            // Toggle sidebar on mobile
            if (toggleSidebarBtn) {
                toggleSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Scroll to bottom of chat
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Message polling interval (in milliseconds)
            const POLLING_INTERVAL = 2000;
            let lastMessageId = <?php echo count($messages) > 0 ? $messages[count($messages) - 1]['id'] : 0; ?>;
            let oldestMessageId = <?php echo count($messages) > 0 ? $messages[0]['id'] : 'Number.MAX_SAFE_INTEGER'; ?>;
            let isLoadingMore = false;
            let hasMoreMessages = true;
            
            // Check if we have more messages to load
            checkForOlderMessages();
            
            // Start polling for new messages
            const messageInterval = setInterval(loadMessages, POLLING_INTERVAL);
            
            // Clean up intervals when page is unloaded
            window.addEventListener('beforeunload', function() {
                clearInterval(messageInterval);
            });
            
            // Handle message submission
            if (messageForm) {
                messageForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const message = messageInput.value.trim();
                    if (!message) return;
                    
                    // Clear input
                    messageInput.value = '';
                    
                    try {
                        // Send message to server
                        const response = await fetch('api.php?action=send_private_message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ 
                                receiver_id: receiverId,
                                message: message
                            })
                        });
                        
                        if (!response.ok) {
                            throw new Error('Failed to send message');
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Add message to chat
                            addMessageToChat(data.message);
                            
                            // Update last message ID
                            lastMessageId = data.message.id;
                            
                            // Scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    } catch (error) {
                        console.error('Error sending message:', error);
                        // Add error message to chat
                        addSystemMessage('Failed to send message. Please try again.');
                    }
                });
            }
            
            // Load more messages when button is clicked
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    loadOlderMessages();
                });
            }
            
            // Check if there are older messages to load
            async function checkForOlderMessages() {
                if (oldestMessageId === Number.MAX_SAFE_INTEGER || oldestMessageId === 0) {
                    return; // No messages yet
                }
                
                try {
                    const response = await fetch(`api.php?action=get_private_messages&user_id=${receiverId}&last_id=${oldestMessageId}&direction=older&limit=1`);
                    
                    if (!response.ok) {
                        throw new Error('Failed to check for older messages');
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.total > <?php echo count($messages); ?>) {
                        // Show load more button
                        loadMoreContainer.style.display = 'flex';
                        hasMoreMessages = true;
                    } else {
                        hasMoreMessages = false;
                        loadMoreContainer.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error checking for older messages:', error);
                }
            }
            
            // Load messages from server
            async function loadMessages() {
                try {
                    const response = await fetch(`api.php?action=get_private_messages&user_id=${receiverId}&last_id=${lastMessageId}&direction=newer`);
                    
                    if (!response.ok) {
                        throw new Error('Failed to load messages');
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.messages && data.messages.length > 0) {
                        // Update last message ID
                        const newMessages = data.messages;
                        lastMessageId = newMessages[newMessages.length - 1].id;
                        
                        // Add messages to chat
                        newMessages.forEach(message => {
                            addMessageToChat(message);
                        });
                        
                        // Scroll to bottom if user was already at bottom
                        const isAtBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 100;
                        if (isAtBottom) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                        
                        // Mark messages as read
                        fetch(`api.php?action=mark_messages_read&sender_id=${receiverId}`);
                    }
                } catch (error) {
                    console.error('Error loading messages:', error);
                }
            }
            
            // Load older messages
            async function loadOlderMessages() {
                if (isLoadingMore) return;
    
                isLoadingMore = true;
                loadMoreBtn.textContent = 'Loading...';
                loadMoreBtn.disabled = true;
    
                try {
                    const response = await fetch(`api.php?action=get_private_messages&user_id=${receiverId}&last_id=${oldestMessageId}&direction=older`);
    
                    if (!response.ok) {
                        throw new Error('Failed to load older messages');
                    }
    
                    const data = await response.json();
    
                    if (data.success && data.messages && data.messages.length > 0) {
                        // Update oldest message ID
                        const oldMessages = data.messages;
                        oldestMessageId = oldMessages[0].id;
    
                        // Remember scroll position
                        const scrollHeight = chatMessages.scrollHeight;
    
                        // Add messages to the beginning of the chat
                        oldMessages.forEach(message => {
                            prependMessageToChat(message);
                        });
    
                        // Maintain scroll position
                        chatMessages.scrollTop = chatMessages.scrollHeight - scrollHeight;
    
                        // Hide load more button if no more messages
                        if (data.messages.length === 0) {
                            loadMoreContainer.style.display = 'none';
                        }
                    } else {
                        // No more messages
                        loadMoreContainer.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error loading older messages:', error);
                    addSystemMessage('Failed to load older messages. Please try again.');
                } finally {
                    isLoadingMore = false;
                    loadMoreBtn.textContent = 'Load Older Messages';
                    loadMoreBtn.disabled = false;
                }
            }
            
            // Add message to chat
            function addMessageToChat(message) {
                // Check if message already exists
                if (document.querySelector(`[data-message-id="${message.id}"]`)) {
                    return;
                }
                
                const messageElement = document.createElement('div');
                const isCurrentUser = message.sender_id == <?php echo $currentUser['id']; ?>;
                
                messageElement.className = `message-bubble ${isCurrentUser ? 'sent' : 'received'}`;
                messageElement.dataset.messageId = message.id;
                
                messageElement.innerHTML = `
                    <div class="message-content">${escapeHtml(message.message)}</div>
                    <div class="message-info">
                        <span class="message-time">${formatDate(message.created_at)}</span>
                        ${isCurrentUser && message.is_read ? '<span class="message-status">Read</span>' : ''}
                    </div>
                `;
                
                chatMessages.appendChild(messageElement);
            }
            
            // Prepend message to chat (for loading older messages)
            function prependMessageToChat(message) {
                // Check if message already exists
                if (document.querySelector(`[data-message-id="${message.id}"]`)) {
                    return;
                }
                
                const messageElement = document.createElement('div');
                const isCurrentUser = message.sender_id == <?php echo $currentUser['id']; ?>;
                
                messageElement.className = `message-bubble ${isCurrentUser ? 'sent' : 'received'}`;
                messageElement.dataset.messageId = message.id;
                
                messageElement.innerHTML = `
                    <div class="message-content">${escapeHtml(message.message)}</div>
                    <div class="message-info">
                        <span class="message-time">${formatDate(message.created_at)}</span>
                        ${isCurrentUser && message.is_read ? '<span class="message-status">Read</span>' : ''}
                    </div>
                `;
                
                // Insert at the beginning of chat messages
                chatMessages.insertBefore(messageElement, chatMessages.firstChild);
            }
            
            // Add system message
            function addSystemMessage(text) {
                const messageElement = document.createElement('div');
                messageElement.className = 'message-center';
                messageElement.textContent = text;
                
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Format date
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            
            // Escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
</body>
</html>
