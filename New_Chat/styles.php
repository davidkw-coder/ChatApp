<?php
header("Content-type: text/css");
?>

/* Base styles */
:root {
    --primary-color: #4f46e5;
    --primary-hover: #4338ca;
    --secondary-color: #6b7280;
    --secondary-hover: #4b5563;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --light-color: #f3f4f6;
    --dark-color: #1f2937;
    --text-color: #374151;
    --text-light: #6b7280;
    --border-color: #e5e7eb;
    --bg-color: #ffffff;
    --bg-light: #f9fafb;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --radius: 0.375rem;
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: var(--bg-light);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

a {
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--transition);
}

a:hover {
    color: var(--primary-hover);
}

ul {
    list-style: none;
}

img {
    max-width: 100%;
}

/* Container */
.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Header */
header {
    background-color: var(--bg-color);
    box-shadow: var(--shadow);
    padding: 1rem 0;
    margin-bottom: 2rem;
}

.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header h1 {
    font-size: 1.8rem;
    font-weight: 700;
}

header h1 a {
    color: var(--dark-color);
}

header nav ul {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-weight: 500;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    color: white;
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: white;
}

.btn-secondary:hover {
    background-color: var(--secondary-hover);
    color: white;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-outline:hover {
    background-color: var(--light-color);
}

.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #dc2626;
    color: white;
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: #059669;
    color: white;
}

/* Footer */
footer {
    text-align: center;
    padding: 2rem 0;
    margin-top: auto;
    color: var(--text-light);
    border-top: 1px solid var(--border-color);
    background-color: var(--bg-color);
}

/* Landing page */
.landing-content {
    padding: 2rem 0;
}

.hero {
    text-align: center;
    padding: 3rem 1rem;
    max-width: 800px;
    margin: 0 auto;
}

.hero h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--dark-color);
}

.hero p {
    font-size: 1.25rem;
    color: var(--text-light);
    margin-bottom: 2rem;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 4rem;
}

.feature-card {
    background-color: var(--bg-color);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.feature-card h3 {
    margin-bottom: 0.5rem;
    color: var(--dark-color);
}

/* Forms */
.form-container {
    background-color: var(--bg-color);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    max-width: 500px;
    margin: 0 auto;
}

.form-container h2 {
    margin-bottom: 1.5rem;
    color: var(--dark-color);
    text-align: center;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 1rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
}

.form-links {
    text-align: center;
    margin-top: 1.5rem;
    color: var(--text-light);
}

/* Alerts */
.alert {
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
}

.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-warning {
    background-color: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.alert-info {
    background-color: rgba(79, 70, 229, 0.1);
    color: var(--primary-color);
    border: 1px solid rgba(79, 70, 229, 0.2);
}

/* Chat styles */
.chat-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    height: calc(100vh - 140px);
    background-color: var(--bg-color);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.sidebar {
    background-color: var(--bg-color);
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.user-profile {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.user-avatar {
    margin-right: 1rem;
}

.user-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-info h3 {
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.status {
    font-size: 0.75rem;
    display: flex;
    align-items: center;
}

.status::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.status.online::before {
    background-color: var(--success-color);
}

.status.offline::before {
    background-color: var(--secondary-color);
}

.sidebar-section {
    padding: 1.5rem;
    flex: 1;
    overflow-y: auto;
}

.sidebar-section h4 {
    font-size: 0.875rem;
    text-transform: uppercase;
    color: var(--text-light);
    margin-bottom: 1rem;
}

.user-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.user-list li {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: var(--radius);
    transition: var(--transition);
    cursor: pointer;
}

.user-list li:hover {
    background-color: var(--bg-light);
}

.user-list li.active {
    background-color: rgba(79, 70, 229, 0.1);
}

.user-list li.loading {
    color: var(--text-light);
    font-style: italic;
}

.user-list .user-avatar {
    width: 32px;
    height: 32px;
    margin-right: 0.75rem;
}

.user-list .user-avatar img {
    width: 32px;
    height: 32px;
}

.sidebar-actions {
    display: flex;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color);
}

.chat-main {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chat-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background-color: var(--bg-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header h2 {
    font-size: 1.25rem;
    color: var(--dark-color);
}

.chat-header .user-info {
    display: flex;
    align-items: center;
}

.chat-header .user-avatar {
    width: 40px;
    height: 40px;
    margin-right: 1rem;
}

.chat-header .user-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.chat-messages {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
    background-color: var(--bg-light);
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    position: relative;
}

.message-bubble.sent {
    align-self: flex-end;
    background-color: var(--primary-color);
    color: white;
    border-bottom-right-radius: 0;
}

.message-bubble.received {
    align-self: flex-start;
    background-color: var(--bg-color);
    border-bottom-left-radius: 0;
}

.message-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

.message-sender {
    font-weight: 600;
}

.message-time {
    color: var(--text-light);
}

.message-status {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.7);
}

.message-bubble.sent .message-info {
    color: rgba(255, 255, 255, 0.8);
}

.message-center {
    align-self: center;
    background-color: var(--bg-color);
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    color: var(--text-light);
    font-style: italic;
}

.chat-input-container {
    padding: 1rem 1.5rem;
    background-color: var(--bg-color);
    border-top: 1px solid var(--border-color);
}

.chat-input-container form {
    display: flex;
    gap: 0.75rem;
}

.chat-input-container input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 1rem;
    transition: var(--transition);
}

.chat-input-container input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Chat list styles */
.chat-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chat-list-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.chat-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chat-list-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: var(--radius);
    background-color: var(--bg-color);
    box-shadow: var(--shadow);
    cursor: pointer;
    transition: var(--transition);
}

.chat-list-item:hover {
    transform: translateY(-2px);
}

.chat-info {
    flex: 1;
    margin-left: 1rem;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.chat-header h3 {
    font-size: 1rem;
    margin: 0;
}

.chat-time {
    font-size: 0.75rem;
    color: var(--text-light);
}

.chat-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-preview p {
    font-size: 0.875rem;
    color: var(--text-light);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 80%;
}

.unread-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background-color: var(--primary-color);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid var(--bg-color);
}

.status-indicator.online {
    background-color: var(--success-color);
}

.status-indicator.offline {
    background-color: var(--secondary-color);
}

.friends-section {
    margin-top: 2rem;
}

.friends-section h3 {
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.friends-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.friend-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: var(--radius);
    background-color: var(--bg-color);
    box-shadow: var(--shadow);
    cursor: pointer;
    transition: var(--transition);
}

.friend-item:hover {
    transform: translateY(-2px);
}

.friend-info {
    margin-left: 0.75rem;
}

.friend-info h3 {
    font-size: 0.875rem;
    margin: 0 0 0.25rem 0;
}

.status-text {
    font-size: 0.75rem;
    color: var(--text-light);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background-color: var(--bg-color);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.empty-state p {
    margin-bottom: 1.5rem;
    color: var(--text-light);
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background-color: var(--danger-color);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

/* Profile styles */
.profile-container {
    max-width: 600px;
}

.profile-avatar {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2rem;
}

.profile-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 1rem;
    border: 3px solid var(--primary-color);
}

.password-change {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.password-change h3 {
    margin-bottom: 1.5rem;
    color: var(--dark-color);
}

/* Feed styles */
.feed-container {
    max-width: 800px;
    margin: 0 auto;
}

.create-post {
    background-color: var(--bg-color);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.create-post textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    resize: none;
    font-size: 1rem;
    margin-bottom: 1rem;
}

.create-post textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.post-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.post-attachments {
    display: flex;
    gap: 1rem;
}

.post-card {
    background-color: var(--bg-color);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.post-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.post-header .user-avatar {
    width: 40px;
    height: 40px;
}

.post-header .user-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.post-header .user-info {
    flex: 1;
}

.post-header .post-time {
    font-size: 0.75rem;
    color: var(--text-light);
}

.post-content {
    padding: 1rem;
    white-space: pre-wrap;
}

.post-image {
    width: 100%;
    max-height: 500px;
    object-fit: contain;
}

.post-footer {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    border-top: 1px solid var(--border-color);
}

.post-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-light);
}

.post-actions {
    display: flex;
    gap: 1rem;
}

.post-action {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: var(--transition);
}

.post-action:hover {
    color: var(--primary-color);
}

.post-action.liked {
    color: var(--primary-color);
}

.comments-section {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    background-color: var(--bg-light);
}

.comment-form {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.comment-form input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 0.875rem;
}

.comment-form input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.comment-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.comment {
    display: flex;
    gap: 0.75rem;
}

.comment .user-avatar {
    width: 32px;
    height: 32px;
}

.comment .user-avatar img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-content {
    flex: 1;
    background-color: var(--bg-color);
    padding: 0.75rem;
    border-radius: var(--radius);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.comment-username {
    font-weight: 600;
    font-size: 0.875rem;
}

.comment-time {
    font-size: 0.75rem;
    color: var(--text-light);
}

.comment-text {
    font-size: 0.875rem;
}

.load-more {
    text-align: center;
    margin: 1.5rem 0;
}

/* Responsive */
@media (max-width: 768px) {
    .chat-container {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        display: none;
    }
    
    .sidebar.active {
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        width: 280px;
        height: 100%;
        z-index: 100;
    }
    
    .header-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    header nav ul {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .hero h2 {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .form-container {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}

