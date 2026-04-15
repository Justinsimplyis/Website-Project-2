<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

include 'db_connection.php';
 $user_id = $_SESSION['user_id'];
 $content = trim($_POST['content'] ?? '');

if (empty($content)) {
    echo json_encode(['error' => 'Post cannot be empty']);
    exit();
}

// Insert Post
 $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
 $stmt->bind_param("is", $user_id, $content);
 $stmt->execute();
 $post_id = $stmt->insert_id;
 $stmt->close();

// Fetch the newly created post with user info to return as HTML
 $sql = "SELECT p.*, u.username, up.profile_image 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN users_profile up ON u.id = up.user_id 
        WHERE p.id = ?";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("i", $post_id);
 $stmt->execute();
 $post = $stmt->get_result()->fetch_assoc();
 $stmt->close();
 $conn->close();

// Generate the HTML for the new post
 $avatar = !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/295/295128.png';
 $time_ago = 'just now';

 $html = '<div class="post-card glass-box" data-post-id="' . $post['id'] . '">';
 $html .= '<div class="post-header d-flex align-items-center gap-3">';
 $html .= '<img src="' . $avatar . '" class="post-avatar">';
 $html .= '<div><strong>' . htmlspecialchars($post['username']) . '</strong><br><small class="text-muted">' . $time_ago . '</small></div>';
 $html .= '<button class="btn btn-sm btn-link text-danger ms-auto delete-post-btn" title="Delete Post"><i class="fa fa-trash"></i></button>';
 $html .= '</div>';
 $html .= '<div class="post-body mt-3">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
 $html .= '<div class="post-actions mt-3 border-top pt-2 d-flex gap-4">';
 $html .= '<button class="btn btn-sm btn-light like-btn"><i class="fa fa-heart-o"></i> <span>0</span> Likes</button>';
 $html .= '<span class="btn btn-sm btn-light disabled"><i class="fa fa-comment-o"></i> 0 Comments</span>';
 $html .= '</div></div>';

echo json_encode(['success' => true, 'html' => $html]);
?>