<?php
require_once 'config.php';

// Redirect to chat if already logged in
if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp - Connect with friends</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <main class="landing-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="hero">
                <h2>Connect with friends in real-time</h2>
                <p>Join thousands of users already chatting on our platform</p>
                <div class="cta-buttons">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>
            
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Real-time Messaging</h3>
                    <p>Send and receive messages instantly</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>User Profiles</h3>
                    <p>Customize your profile and connect with others</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure</h3>
                    <p>Your conversations are private and secure</p>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

