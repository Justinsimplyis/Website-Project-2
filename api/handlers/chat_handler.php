<?php
session_start();
header('Content-Type: application/json');
include 'C:/Users/User/Documents/GitHub/Website-Project-2/database/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

 $user_id = (int) $_SESSION['user_id'];
 $action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_rooms':
        $sql = "SELECT cr.id AS room_id, 
                       u.id AS contact_id, u.username, up.profile_image,
                       (SELECT message FROM messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) AS last_message,
                       (SELECT created_at FROM messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) AS last_time,
                       (SELECT COUNT(*) FROM messages WHERE room_id = cr.id AND sender_id != ? AND is_read = 0) AS unread
                FROM chat_room_members crm
                JOIN chat_rooms cr ON crm.room_id = cr.id
                JOIN chat_room_members crm2 ON cr.id = crm2.room_id AND crm2.user_id != ?
                JOIN users u ON crm2.user_id = u.id
                LEFT JOIN users_profile up ON u.id = up.user_id
                WHERE crm.user_id = ? AND cr.is_group = 0
                GROUP BY cr.id
                ORDER BY last_time DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'rooms' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

        case 'start_chat':
        $target_id = (int) $_POST['target_user_id'];
        $user_role = $_SESSION['role'] ?? 'user';
        
        if ($user_id === $target_id) {
            echo json_encode(['success' => false, 'error' => 'You cannot message yourself.']);
            break;
        }

        // 1. Block check (Applies to everyone)
        $block = $conn->prepare("SELECT 1 FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
        $block->bind_param("iiii", $user_id, $target_id, $target_id, $user_id);
        $block->execute();
        if ($block->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot message this user.']);
            break;
        }

        // 2. FOLLOW CHECK (Bypass for Admins and Moderators)
        if (!in_array($user_role, ['admin', 'moderator'])) {
            $follow_check = $conn->prepare("SELECT 1 FROM followers WHERE (follower_id = ? AND followed_id = ?) OR (follower_id = ? AND followed_id = ?)");
            $follow_check->bind_param("iiii", $user_id, $target_id, $target_id, $user_id);
            $follow_check->execute();
            if ($follow_check->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'You can only message your followers or users you follow.']);
                break;
            }
        }

        // 3. Find existing room
        $find = $conn->prepare("SELECT crm.room_id FROM chat_room_members crm JOIN chat_room_members crm2 ON crm.room_id = crm2.room_id JOIN chat_rooms cr ON crm.room_id = cr.id WHERE crm.user_id = ? AND crm2.user_id = ? AND cr.is_group = 0 LIMIT 1");
        $find->bind_param("ii", $user_id, $target_id);
        $find->execute();
        $room = $find->get_result()->fetch_assoc();

        if (!$room) {
            // 4. Create room if not exists
            $conn->query("INSERT INTO chat_rooms (is_group) VALUES (0)");
            $room_id = $conn->insert_id;
            $ins = $conn->prepare("INSERT INTO chat_room_members (room_id, user_id) VALUES (?, ?), (?, ?)");
            $ins->bind_param("iiii", $room_id, $user_id, $room_id, $target_id);
            $ins->execute();
        } else {
            $room_id = $room['room_id'];
        }
        
        // NEW: Fetch target user's info so the frontend doesn't show "User"
        $user_info = $conn->prepare("SELECT u.username, up.profile_image FROM users u LEFT JOIN users_profile up ON u.id = up.user_id WHERE u.id = ?");
        $user_info->bind_param("i", $target_id);
        $user_info->execute();
        $target_data = $user_info->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'room_id' => $room_id,
            'username' => $target_data['username'],
            'profile_image' => $target_data['profile_image']
        ]);
        break;

    case 'get_messages':
        $room_id = (int) $_GET['room_id'];
        $last_id = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;

        // Mark messages as read
        $upd = $conn->prepare("UPDATE messages SET is_read = 1 WHERE room_id = ? AND sender_id != ? AND is_read = 0");
        $upd->bind_param("ii", $room_id, $user_id);
        $upd->execute();

        // Fetch messages
        $sql = "SELECT m.*, u.username, up.profile_image 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                LEFT JOIN users_profile up ON u.id = up.user_id 
                WHERE m.room_id = ? AND m.id > ? 
                ORDER BY m.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $room_id, $last_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'messages' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

        case 'send_message':
        $room_id = (int) $_POST['room_id'];
        $message = trim($_POST['message'] ?? '');
        
        $media_url = null;
        $media_type = 'none';

        // 1. Handle Image Upload
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media'];
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Check if it's actually an image and under 5MB
            if (in_array($ext, $allowed) && $file['size'] < 5000000) {
                $project_root = 'C:/Users/User/Documents/GitHub/Website-Project-2/';
                $upload_dir = $project_root . 'uploads/chat_media/';
                $web_path = '/uploads/chat_media/';

                // Create folder if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Create unique filename
                $file_name = uniqid() . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $file_name)) {
                    $media_url = $web_path . $file_name;
                    $media_type = 'image';
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid image or file too large (Max 5MB).']);
                exit();
            }
        }

        // 2. Prevent sending empty messages
        if (empty($message) && $media_type === 'none') {
            echo json_encode(['success' => false, 'error' => 'Cannot send empty message.']);
            exit();
        }

        if ($room_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid room ID.']);
            exit();
        }

        // 3. Insert into DB (now includes media_type and media_url)
        $stmt = $conn->prepare("INSERT INTO messages (room_id, sender_id, message, media_type, media_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $room_id, $user_id, $message, $media_type, $media_url);
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Database insert failed.']);
            exit();
        }
        
        $msg_id = $stmt->insert_id;

        // 4. Create notification (same as before)
        $rec = $conn->prepare("SELECT user_id FROM chat_room_members WHERE room_id = ? AND user_id != ?");
        $rec->bind_param("ii", $room_id, $user_id);
        $rec->execute();
        $res = $rec->get_result();
        
        if ($res->num_rows > 0) {
            $recipient_id = $res->fetch_assoc()['user_id'];
            $notif_msg = $media_type === 'image' ? 'Sent you an image' : 'Sent you a message';
            $notif = $conn->prepare("INSERT INTO notifications (recipient_id, sender_id, type, message, related_id, related_type) VALUES (?, ?, 'message', ?, ?, 'message')");
            $notif->bind_param("iisi", $recipient_id, $user_id, $notif_msg, $msg_id);
            $notif->execute();
        }

        echo json_encode(['success' => true]);
        exit();
}