<header>
    <div class="container header-container">
        <h1><a href="index.php">ChatApp</a></h1>
        <nav>
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="chat.php">Chat</a></li>
                    <li><a href="feed.php">Feed</a></li>
                    <li><a href="friends.php">Friends</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

