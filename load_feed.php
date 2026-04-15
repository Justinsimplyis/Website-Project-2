<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit();
}

include 'db_connection.php';
 $user_id = $_SESSION['user_id'];

// Complex query to get posts, user info, like counts, comment counts, and whether the current user liked it
 $sql = "SELECT 
            p.*, 
            u.username, 
            up.profile_image,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count,
            MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) as user_has_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN users_profile up ON u.id = up.user_id
        LEFT JOIN post_likes l ON p.id = l.post_id
        LEFT JOIN post_comments c ON p.id = c.post_id
        WHERE p.is_deleted = 0 
          AND (p.user_id = ? OR p.user_id IN (SELECT followed_id FROM followers WHERE follower_id = ?))
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 20";

 $stmt = $conn->prepare($sql);
// Bind user_id three times for the query placeholders
 $stmt->bind_param("iii", $user_id, $user_id, $user_id);
 $stmt->execute();
 $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 $stmt->close();
 $conn->close();

function getTimeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    elseif ($diff < 3600) return floor($diff / 60) . 'm ago';
    elseif ($diff < 86400) return floor($diff / 3600) . 'h ago';
    elseif ($diff < 604800) return floor($diff / 86400) . 'd ago';
    else return date("M j", strtotime($datetime));
}

if (count($posts) > 0) {
    foreach ($posts as $post) {
        $avatar = !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/295/295128.png';
        $time_ago = getTimeAgo($post['created_at']);
        $is_owner = ($post['user_id'] == $user_id);
        
        $heart_icon = $post['user_has_liked'] ? 'fa-heart text-danger' : 'fa-heart-o';

        echo '<div class="post-card glass-box mb-4" data-post-id="' . $post['id'] . '">';
        echo '<div class="post-header d-flex align-items-center gap-3">';
        echo '<img src="' . $avatar . '" class="post-avatar">';
        echo '<div><strong>' . htmlspecialchars($post['username']) . '</strong><br><small class="text-muted">' . $time_ago . '</small></div>';
        
        if ($is_owner) {
            echo '<button class="btn btn-sm btn-link text-danger ms-auto delete-post-btn" title="Delete Post"><i class="fa fa-trash"></i></button>';
        }
        
        echo '</div>';
        echo '<div class="post-body mt-3">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
        echo '<div class="post-actions mt-3 border-top pt-2 d-flex gap-4">';
        echo '<button class="btn btn-sm btn-light like-btn ' . ($post['user_has_liked'] ? 'active' : '') . '">';
        echo '<i class="fa ' . $heart_icon . '"></i> <span>' . $post['like_count'] . '</span> Likes</button>';
        echo '<span class="btn btn-sm btn-light disabled"><i class="fa fa-comment-o"></i> ' . $post['comment_count'] . ' Comments</span>';
        echo '</div></div>';
    }
} else {
    echo '<div class="text-center text-muted mt-5"><i class="fa fa-rss fa-3x mb-3"></i><p>No posts yet. Be the first to post!</p></div>';
}
?>