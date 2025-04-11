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

// Get unread private messages count
$conn = getDbConnection();
$unreadCount = 0;

if ($conn !== false) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM private_messages 
        WHERE receiver_id = ? AND is_read = 0
    ");

    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("i", $currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $unreadCount = $result->fetch_assoc()['count'];
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - ChatApp</title>
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
                    <h4>Active Users</h4>
                    <ul id="active-users" class="user-list">
                        <li class="loading">Loading users...</li>
                    </ul>
                </div>
                
                <div class="sidebar-actions">
                    <a href="profile.php" class="btn btn-secondary btn-sm">Profile</a>
                    <a href="chat-list.php" class="btn btn-primary btn-sm">
                        Private Chats
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
                </div>
            </aside>
            
            <main class="chat-main">
                <div class="chat-header">
                    <h2>Public Chat Room</h2>
                    <button id="toggle-sidebar" class="btn btn-outline btn-sm d-md-none">Users</button>
                </div>
                
                <div class="chat-content">
                    <div id="load-more-container" class="load-more-container">
                        <button id="load-more-btn" class="btn btn-primary btn-sm">Load Older Messages</button>
                    </div>
                    
                    <div id="chat-messages" class="chat-messages">
                        <div class="message-center">
                            <p>Welcome to the chat room!</p>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input-container">
                    <form id="message-form">
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
            const activeUsersList = document.getElementById('active-users');
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
            
            // Message polling interval (in milliseconds)
            const POLLING_INTERVAL = 2000;
            let lastMessageId = 0;
            let oldestMessageId = Number.MAX_SAFE_INTEGER;
            let isLoadingMore = false;
            let hasMoreMessages = true;
            
            // Load initial messages
            loadMessages();
            
            // Start polling for new messages
            const messageInterval = setInterval(loadMessages, POLLING_INTERVAL);
            
            // Load active users
            loadActiveUsers();
            
            // Start polling for active users
            const userInterval = setInterval(loadActiveUsers, 10000);
            
            // Clean up intervals when page is unloaded
            window.addEventListener('beforeunload', function() {
                clearInterval(messageInterval);
                clearInterval(userInterval);
                
                // Update user status to offline
                fetch('api.php?action=update_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ status: 'offline' })
                }).catch(err => console.error('Error updating status:', err));
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
                        const response = await fetch('api.php?action=send_message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ message })
                        });
                        
                        if (!response.ok) {
                            throw new Error('Failed to send message');
                        }
                        
                        // Load latest messages (including the one just sent)
                        loadMessages();
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
            
            // Load messages from server
            async function loadMessages() {
                try {
                    const response = await fetch(`api.php?action=get_messages&last_id=${lastMessageId}&direction=newer`);
                    
                    if (!response.ok) {
                        throw new Error('Failed to load messages');
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.messages && data.messages.length > 0) {
                        // Update last message ID
                        const newMessages = data.messages;
                        lastMessageId = newMessages[newMessages.length - 1].id;
                        
                        // Update oldest message ID if this is the first load
                        if (oldestMessageId === Number.MAX_SAFE_INTEGER && newMessages.length > 0) {
                            oldestMessageId = newMessages[0].id;
                            
                            // Show load more button if there are more messages
                            if (data.total > newMessages.length) {
                                loadMoreContainer.style.display = 'flex';
                                hasMoreMessages = true;
                            }
                        }
                        
                        // Add messages to chat
                        newMessages.forEach(message => {
                            addMessageToChat(message);
                        });
                        
                        // Scroll to bottom if user was already at bottom
                        const isAtBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 100;
                        if (isAtBottom) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
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
                    const response = await fetch(`api.php?action=get_messages&last_id=${oldestMessageId}&direction=older`);
                    
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
                        hasMoreMessages = data.has_more;
                        if (!hasMoreMessages) {
                            loadMoreContainer.style.display = 'none';
                        }
                    } else {
                        // No more messages
                        hasMoreMessages = false;
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
            
            // Load active users
            async function loadActiveUsers() {
                try {
                    const response = await fetch('api.php?action=get_active_users');
                    
                    if (!response.ok) {
                        throw new Error('Failed to load active users');
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.users) {
                        // Clear current list
                        activeUsersList.innerHTML = '';
                        
                        // Add users to list
                        data.users.forEach(user => {
                            if (user.id != <?php echo $currentUser['id']; ?>) {
                                const userItem = document.createElement('li');
                                
                                userItem.innerHTML = `
                                    <div class="user-avatar">
                                        <img src="${user.avatar || 'uploads/default-avatar.png'}" alt="${escapeHtml(user.username)}">
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name">${escapeHtml(user.username)}</span>
                                        <span class="status ${user.status}">${user.status}</span>
                                    </div>
                                `;
                                
                                userItem.addEventListener('click', function() {
                                    window.location.href = `personal-chat.php?user=${user.id}`;
                                });
                                
                                activeUsersList.appendChild(userItem);
                            }
                        });
                        
                        // If no users, show message
                        if (data.users.length <= 1) {
                            const noUsers = document.createElement('li');
                            noUsers.className = 'loading';
                            noUsers.textContent = 'No other active users';
                            activeUsersList.appendChild(noUsers);
                        }
                    }
                } catch (error) {
                    console.error('Error loading active users:', error);
                    
                    // Show error message
                    activeUsersList.innerHTML = '<li class="loading">Failed to load users</li>';
                }
            }
            
            // Add message to chat
            function addMessageToChat(message) {
                // Check if message already exists
                if (document.querySelector(`[data-message-id="${message.id}"]`)) {
                    return;
                }
                
                const messageElement = document.createElement('div');
                const isCurrentUser = message.user_id === <?php echo $currentUser['id']; ?>;
                
                messageElement.className = `message-bubble ${isCurrentUser ? 'sent' : 'received'}`;
                messageElement.dataset.messageId = message.id;
                
                messageElement.innerHTML = `
                    <div class="message-content">${escapeHtml(message.message)}</div>
                    <div class="message-info">
                        <span class="message-sender">${escapeHtml(message.username)}</span>
                        <span class="message-time">${formatDate(message.created_at)}</span>
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
                const isCurrentUser = message.user_id === <?php echo $currentUser['id']; ?>;
                
                messageElement.className = `message-bubble ${isCurrentUser ? 'sent' : 'received'}`;
                messageElement.dataset.messageId = message.id;
                
                messageElement.innerHTML = `
                    <div class="message-content">${escapeHtml(message.message)}</div>
                    <div class="message-info">
                        <span class="message-sender">${escapeHtml(message.username)}</span>
                        <span class="message-time">${formatDate(message.created_at)}</span>
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
