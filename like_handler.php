<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

include 'db_connection.php';
 $user_id = $_SESSION['user_id'];
 $post_id = intval($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    echo json_encode(['error' => 'Invalid post']);
    exit();
}

// Check if already liked
 $check_sql = "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?";
 $stmt = $conn->prepare($check_sql);
 $stmt->bind_param("ii", $post_id, $user_id);
 $stmt->execute();
 $is_liked = $stmt->get_result()->num_rows > 0;
 $stmt->close();

if ($is_liked) {
    // UNLIKE
    $stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $action = 'unliked';
} else {
    // LIKE
    $stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $action = 'liked';
}

// Get new total like count
 $count_sql = "SELECT COUNT(*) as total FROM post_likes WHERE post_id = ?";
 $stmt = $conn->prepare($count_sql);
 $stmt->bind_param("i", $post_id);
 $stmt->execute();
 $new_count = $stmt->get_result()->fetch_assoc()['total'];
 $stmt->close();
 $conn->close();

echo json_encode(['success' => true, 'action' => $action, 'new_count' => $new_count]);
?>