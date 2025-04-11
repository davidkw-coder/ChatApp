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

$success = '';
$error = '';

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = $_POST['content'] ?? '';
    
    if (empty($content)) {
        $error = 'Post content cannot be empty';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Check if image was uploaded
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/posts/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Get file info
                $fileName = $_FILES['image']['name'];
                $fileType = $_FILES['image']['type'];
                $fileTmpName = $_FILES['image']['tmp_name'];
                $fileError = $_FILES['image']['error'];
                $fileSize = $_FILES['image']['size'];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    $error = 'Only JPG, PNG, and GIF files are allowed';
                } elseif ($fileSize > 5000000) { // 5MB max
                    $error = 'File size must be less than 5MB';
                } else {
                    // Generate unique filename
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = uniqid() . '.' . $fileExtension;
                    $targetFilePath = $uploadDir . $newFileName;
                    
                    // Upload file
                    if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                        $imagePath = $targetFilePath;
                    } else {
                        $error = 'Failed to upload image';
                    }
                }
            }
            
            if (empty($error)) {
                // Insert post
                $stmt = $conn->prepare("
                    INSERT INTO posts (user_id, content, image, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                
                if ($stmt === false) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("iss", $currentUser['id'], $content, $imagePath);
                    
                    if ($stmt->execute()) {
                        $success = 'Post created successfully';
                        
                        // Redirect to avoid form resubmission
                        header('Location: feed.php?success=post_created');
                        exit;
                    } else {
                        $error = 'Failed to create post: ' . $conn->error;
                    }
                }
            }
            
            $conn->close();
        }
    }
}

// Handle post like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $postId = $_POST['post_id'] ?? 0;
    
    if (empty($postId)) {
        $error = 'Invalid post';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Check if already liked
            $stmt = $conn->prepare("
                SELECT id FROM post_likes 
                WHERE post_id = ? AND user_id = ?
            ");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("ii", $postId, $currentUser['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Unlike post
                    $stmt = $conn->prepare("
                        DELETE FROM post_likes 
                        WHERE post_id = ? AND user_id = ?
                    ");
                    
                    if ($stmt === false) {
                        $error = 'Database error: ' . $conn->error;
                    } else {
                        $stmt->bind_param("ii", $postId, $currentUser['id']);
                        
                        if ($stmt->execute()) {
                            // Update post likes count
                            $stmt = $conn->prepare("
                                UPDATE posts 
                                SET likes = (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) 
                                WHERE id = ?
                            ");
                            
                            if ($stmt === false) {
                                $error = 'Database error: ' . $conn->error;
                            } else {
                                $stmt->bind_param("ii", $postId, $postId);
                                $stmt->execute();
                            }
                        }
                    }
                } else {
                    // Like post
                    $stmt = $conn->prepare("
                        INSERT INTO post_likes (post_id, user_id, created_at) 
                        VALUES (?, ?, NOW())
                    ");
                    
                    if ($stmt === false) {
                        $error = 'Database error: ' . $conn->error;
                    } else {
                        $stmt->bind_param("ii", $postId, $currentUser['id']);
                        
                        if ($stmt->execute()) {
                            // Update post likes count
                            $stmt = $conn->prepare("
                                UPDATE posts 
                                SET likes = (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) 
                                WHERE id = ?
                            ");
                            
                            if ($stmt === false) {
                                $error = 'Database error: ' . $conn->error;
                            } else {
                                $stmt->bind_param("ii", $postId, $postId);
                                $stmt->execute();
                            }
                            
                            // Notify post owner if not self
                            $stmt = $conn->prepare("
                                SELECT user_id FROM posts WHERE id = ?
                            ");
                            
                            if ($stmt === false) {
                                error_log("Prepare failed: " . $conn->error);
                            } else {
                                $stmt->bind_param("i", $postId);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $post = $result->fetch_assoc();
                                
                                if ($post && $post['user_id'] != $currentUser['id']) {
                                    $message = $currentUser['username'] . ' liked your post';
                                    $link = 'feed.php?post=' . $postId;
                                    
                                    $stmt = $conn->prepare("
                                        INSERT INTO notifications (user_id, from_user_id, message, link, created_at) 
                                        VALUES (?, ?, ?, ?, NOW())
                                    ");
                                    
                                    if ($stmt === false) {
                                        error_log("Prepare failed: " . $conn->error);
                                    } else {
                                        $stmt->bind_param("iiss", $post['user_id'], $currentUser['id'], $message, $link);
                                        $stmt->execute();
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $conn->close();
            
            if (empty($error)) {
                // Redirect to avoid form resubmission
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }
    }
}

// Handle comment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $postId = $_POST['post_id'] ?? 0;
    $content = $_POST['comment'] ?? '';
    
    if (empty($postId) || empty($content)) {
        $error = 'Comment cannot be empty';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Insert comment
            $stmt = $conn->prepare("
                INSERT INTO comments (post_id, user_id, content, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("iis", $postId, $currentUser['id'], $content);
                
                if ($stmt->execute()) {
                    // Notify post owner if not self
                    $stmt = $conn->prepare("
                        SELECT user_id FROM posts WHERE id = ?
                    ");
                    
                    if ($stmt === false) {
                        error_log("Prepare failed: " . $conn->error);
                    } else {
                        $stmt->bind_param("i", $postId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $post = $result->fetch_assoc();
                        
                        if ($post && $post['user_id'] != $currentUser['id']) {
                            $message = $currentUser['username'] . ' commented on your post';
                            $link = 'feed.php?post=' . $postId;
                            
                            $stmt = $conn->prepare("
                                INSERT INTO notifications (user_id, from_user_id, message, link, created_at) 
                                VALUES (?, ?, ?, ?, NOW())
                            ");
                            
                            if ($stmt === false) {
                                error_log("Prepare failed: " . $conn->error);
                            } else {
                                $stmt->bind_param("iiss", $post['user_id'], $currentUser['id'], $message, $link);
                                $stmt->execute();
                            }
                        }
                    }
                    
                    // Redirect to avoid form resubmission
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                } else {
                    $error = 'Failed to add comment: ' . $conn->error;
                }
            }
            
            $conn->close();
        }
    }
}

// Get success message from query string
if (isset($_GET['success']) && $_GET['success'] === 'post_created') {
    $success = 'Post created successfully';
}

// Get posts
$conn = getDbConnection();
$posts = [];
$totalPages = 1;

if ($conn !== false) {
    // Get single post if post ID is provided
    if (isset($_GET['post'])) {
        $postId = intval($_GET['post']);
        
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        
        if ($stmt === false) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = 'Post not found';
            } else {
                $posts = [$result->fetch_assoc()];
            }
        }
    } else {
        // Get all posts with pagination
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT ?, ?
        ");
        
        if ($stmt === false) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("ii", $offset, $perPage);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
            
            // Get total posts count for pagination
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts");
            
            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
            } else {
                $stmt->execute();
                $result = $stmt->get_result();
                $totalPosts = $result->fetch_assoc()['count'];
                
                $totalPages = ceil($totalPosts / $perPage);
            }
        }
    }

    // Get likes for each post
    foreach ($posts as &$post) {
        // Check if current user liked the post
        $stmt = $conn->prepare("
            SELECT id FROM post_likes 
            WHERE post_id = ? AND user_id = ?
        ");
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            $post['liked'] = false;
        } else {
            $stmt->bind_param("ii", $post['id'], $currentUser['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $post['liked'] = $result->num_rows > 0;
        }
        
        // Get comments for the post
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.avatar 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            $post['comments'] = [];
            $post['comment_count'] = 0;
        } else {
            $stmt->bind_param("i", $post['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $post['comments'] = [];
            while ($row = $result->fetch_assoc()) {
                $post['comments'][] = $row;
            }
            
            $post['comment_count'] = count($post['comments']);
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container feed-container">
        <h2>Social Feed</h2>
        
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
        
        <div class="create-post">
            <h3>Create a Post</h3>
            <form method="POST" action="feed.php" enctype="multipart/form-data">
                <div class="form-group">
                    <textarea name="content" rows="3" placeholder="What's on your mind?" required></textarea>
                </div>
                
                <div class="post-actions">
                    <div class="post-attachments">
                        <label for="image" class="btn btn-outline btn-sm">
                            <span>📷 Add Image</span>
                            <input type="file" id="image" name="image" style="display: none;" accept="image/*" onchange="previewImage(this)">
                        </label>
                        <div id="image-preview"></div>
                    </div>
                    
                    <button type="submit" name="create_post" class="btn btn-primary">Post</button>
                </div>
            </form>
        </div>
        
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="post-header">
                        <div class="user-avatar">
                            <img src="<?php echo !empty($post['avatar']) ? h($post['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($post['username']); ?>">
                        </div>
                        <div class="user-info">
                            <h3><?php echo h($post['username']); ?></h3>
                            <span class="post-time"><?php echo formatDate($post['created_at']); ?></span>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                    
                    <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo h($post['image']); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                    
                    <div class="post-footer">
                        <div class="post-stats">
                            <span><?php echo $post['likes']; ?> likes</span>
                            <span><?php echo $post['comment_count']; ?> comments</span>
                        </div>
                        
                        <div class="post-actions">
                            <form method="POST" action="feed.php">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="like_post" class="post-action <?php echo $post['liked'] ? 'liked' : ''; ?>">
                                    <span>👍</span> Like
                                </button>
                            </form>
                            <div class="post-action" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                <span>💬</span> Comment
                            </div>
                        </div>
                    </div>
                    
                    <div id="comments-<?php echo $post['id']; ?>" class="comments-section" style="display: <?php echo isset($_GET['post']) ? 'block' : 'none'; ?>;">
                        <form method="POST" action="feed.php" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="text" name="comment" placeholder="Write a comment..." required>
                            <button type="submit" name="add_comment" class="btn btn-primary btn-sm">Send</button>
                        </form>
                        
                        <?php if (count($post['comments']) > 0): ?>
                            <div class="comment-list">
                                <?php foreach ($post['comments'] as $comment): ?>
                                    <div class="comment">
                                        <div class="user-avatar">
                                            <img src="<?php echo !empty($comment['avatar']) ? h($comment['avatar']) : 'uploads/default-avatar.png'; ?>" alt="<?php echo h($comment['username']); ?>">
                                        </div>
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <span class="comment-username"><?php echo h($comment['username']); ?></span>
                                                <span class="comment-time"><?php echo formatDate($comment['created_at']); ?></span>
                                            </div>
                                            <div class="comment-text">
                                                <?php echo h($comment['content']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No comments yet. Be the first to comment!</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!isset($_GET['post']) && $totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline btn-sm">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="btn btn-outline btn-sm"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No posts yet. Be the first to create a post!
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxHeight = '100px';
                    img.style.maxWidth = '100px';
                    img.style.marginTop = '10px';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'btn btn-danger btn-sm';
                    removeBtn.style.marginLeft = '10px';
                    removeBtn.onclick = function(e) {
                        e.preventDefault();
                        input.value = '';
                        preview.innerHTML = '';
                    };
                    
                    preview.appendChild(img);
                    preview.appendChild(removeBtn);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function toggleComments(postId) {
            const commentsSection = document.getElementById('comments-' + postId);
            commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
