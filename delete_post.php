<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

include 'db_connection.php';
 $user_id = $_SESSION['user_id'];
 $post_id = intval($_POST['post_id'] ?? 0);
 $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

if ($post_id <= 0) {
    echo json_encode(['error' => 'Invalid post']);
    exit();
}

if ($role === 'admin' || $role === 'moderator') {
    // Admin/Mod can soft-delete ANY post
    $stmt = $conn->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $post_id);
} else {
    // Regular user can only soft-delete THEIR OWN post
    $stmt = $conn->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'You cannot delete this post']);
}
 $stmt->close();
?>