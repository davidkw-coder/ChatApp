<?php
require_once 'config.php';

// Require login
requireLogin();

// Get search query
$query = $_GET['q'] ?? '';
$results = [];

// Perform search if query is provided
if (!empty($query)) {
    $conn = getDbConnection();
    
    // Search for users
    $searchTerm = '%' . $query . '%';
    $stmt = $conn->prepare("
        SELECT id, username, email, avatar, status, last_active
        FROM users
        WHERE username LIKE ? OR email LIKE ?
        ORDER BY username ASC
        LIMIT 20
    ");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>Search Users</h2>
        
        <div class="form-container" style="margin-bottom: 2rem;">
            <form method="GET" action="search.php">
                <div class="form-group">
                    <input type="text" name="q" value="<?php echo h($query); ?>" placeholder="Search by username or email" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($query)): ?>
            <h3>Search Results for "<?php echo h($query); ?>"</h3>
            
            <?php if (count($results) > 0): ?>
                <div class="friends-container">
                    <?php foreach ($results as $user): ?>
                        <div class="friend-card">
                            <img class="friend-avatar" src="<?php echo !empty($user['avatar']) ? h($user['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($user['username']); ?>">
                            <div class="friend-info">
                                <h3><?php echo h($user['username']); ?></h3>
                                <span class="status <?php echo $user['status']; ?>">
                                    <?php echo isUserOnline($user['last_active']) ? 'Online' : 'Offline'; ?>
                                </span>
                            </div>
                            <div class="friend-actions">
                                <a href="profile.php?user=<?php echo $user['username']; ?>" class="btn btn-secondary btn-sm">View Profile</a>
                                <a href="chat.php?user=<?php echo $user['username']; ?>" class="btn btn-primary btn-sm">Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No users found matching "<?php echo h($query); ?>"</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

