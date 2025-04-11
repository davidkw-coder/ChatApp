<?php
require_once 'config.php';

// Require login
requireLogin();

// Check if user is admin
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user['is_admin']) {
    // Not an admin, redirect to home
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Handle user management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($userId === 0) {
        $error = 'Invalid user ID';
    } else {
        switch ($action) {
            case 'ban':
                $stmt = $conn->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $success = 'User banned successfully';
                } else {
                    $error = 'Failed to ban user: ' . $conn->error;
                }
                break;
                
            case 'unban':
                $stmt = $conn->prepare("UPDATE users SET status = 'offline' WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $success = 'User unbanned successfully';
                } else {
                    $error = 'Failed to unban user: ' . $conn->error;
                }
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $success = 'User deleted successfully';
                } else {
                    $error = 'Failed to delete user: ' . $conn->error;
                }
                break;
                
            case 'make_admin':
                $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $success = 'User promoted to admin successfully';
                } else {
                    $error = 'Failed to promote user: ' . $conn->error;
                }
                break;
                
            case 'remove_admin':
                $stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $success = 'Admin privileges removed successfully';
                } else {
                    $error = 'Failed to remove admin privileges: ' . $conn->error;
                }
                break;
                
            default:
                $error = 'Invalid action';
                break;
        }
    }
}

// Get users
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare("
    SELECT id, username, email, status, is_admin, created_at, last_active
    FROM users
    ORDER BY id ASC
    LIMIT ?, ?
");
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get total users count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$result = $stmt->get_result();
$totalUsers = $result->fetch_assoc()['count'];

$totalPages = ceil($totalUsers / $perPage);

// Get system stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages");
$stmt->execute();
$result = $stmt->get_result();
$totalMessages = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'online'");
$stmt->execute();
$result = $stmt->get_result();
$onlineUsers = $result->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: var(--bg-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
            color: var(--primary-color);
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .user-table th,
        .user-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-table th {
            background-color: var(--bg-color);
            font-weight: 600;
        }
        
        .user-table tr:hover {
            background-color: var(--bg-light);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius);
            background-color: var(--bg-color);
            box-shadow: var(--shadow);
        }
        
        .pagination .active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>Admin Panel</h2>
        
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
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Online Users</h3>
                <div class="stat-value"><?php echo $onlineUsers; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Messages</h3>
                <div class="stat-value"><?php echo $totalMessages; ?></div>
            </div>
        </div>
        
        <h3>User Management</h3>
        
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo h($user['username']); ?></td>
                        <td><?php echo h($user['email']); ?></td>
                        <td><?php echo h($user['status']); ?></td>
                        <td><?php echo $user['is_admin'] ? 'Admin' : 'User'; ?></td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                        <td><?php echo formatDate($user['last_active']); ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($user['status'] !== 'banned'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="ban">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to ban this user?')">Ban</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="unban">
                                        <button type="submit" class="btn btn-success btn-sm">Unban</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if (!$user['is_admin']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="make_admin">
                                        <button type="submit" class="btn btn-secondary btn-sm">Make Admin</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="remove_admin">
                                        <button type="submit" class="btn btn-outline btn-sm">Remove Admin</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

