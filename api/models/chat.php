<?php
session_start();
include 'C:/Users/User/Documents/GitHub/Website-Project-2/database/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/auth/login.php");
    exit();
}

 $user_id = $_SESSION['user_id'];
 $username = $_SESSION['username'];
 $role = $_SESSION['role'] ?? 'user';
 $auto_open_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Role Differentiation for Back Button
 $back_url = match($role) {
    'admin' => '/dashboards/admin/admin_dashboard.php',
    'moderator' => '/dashboards/moderator/moderator_dashboard.php',
    default => '/dashboards/users/profile.php'
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - <?php echo htmlspecialchars($username); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: white; height: 100vh; margin: 0; overflow: hidden; display: flex; flex-direction: column; font-family: 'Segoe UI', sans-serif; }
        
        .top-nav { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .back-btn { color: white; text-decoration: none; font-size: 0.9rem; }
        .back-btn:hover { color: #00ffc8; }
        
        .role-badge { padding: 4px 10px; border-radius: 15px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .role-user { background: rgba(108, 117, 125, 0.4); border: 1px solid #6c757d; }
        .role-mod { background: rgba(0, 150, 255, 0.3); border: 1px solid #0096ff; color: #9fd7ff; }
        .role-admin { background: rgba(255, 200, 0, 0.3); border: 1px solid #ffc800; color: #ffe066; }

        .chat-container { display: flex; flex: 1; overflow: hidden; background: rgba(0,0,0,0.2); margin: 10px; border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        
        /* Sidebar */
        .sidebar { width: 320px; background: rgba(0,0,0,0.3); border-right: 1px solid rgba(255,255,255,0.1); border-radius: 15px 0 0 15px; display: flex; flex-direction: column; }
        .sidebar-header { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); font-weight: bold; }
        .room-list { flex: 1; overflow-y: auto; }
        .room-item { display: flex; align-items: center; padding: 12px 15px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.2s; }
        .room-item:hover, .room-item.active { background: rgba(0, 255, 200, 0.1); }
        .room-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 2px solid rgba(255,255,255,0.1); }
        .room-info { flex: 1; overflow: hidden; }
        .room-name { font-weight: 600; font-size: 0.9rem; }
        .room-last-msg { font-size: 0.75rem; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;}
        .unread-badge { background: #00ffc8; color: #000; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; font-weight: bold; }

        /* Main Chat Area */
        .chat-main { flex: 1; display: flex; flex-direction: column; border-radius: 0 15px 15px 0; }
        .chat-header { padding: 15px 20px; background: rgba(0,0,0,0.2); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; border-radius: 0 15px 0 0; }
        .chat-header img { width: 35px; height: 35px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); }
        
        .messages-area { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .msg-bubble { max-width: 65%; padding: 10px 15px; border-radius: 15px; position: relative; word-wrap: break-word; font-size: 0.95rem; }
        .msg-sent { align-self: flex-end; background: #005c4b; border-bottom-right-radius: 2px; }
        .msg-received { align-self: flex-start; background: rgba(255,255,255,0.08); border-bottom-left-radius: 2px; }
        .msg-time { font-size: 0.65rem; color: #aaa; margin-top: 5px; text-align: right; }

        .chat-input-wrapper { display: flex; align-items: center; gap: 10px; flex: 1; position: relative; }
        .attach-btn { background: none; border: none; color: #888; font-size: 1.2rem; cursor: pointer; padding: 5px; }
        .attach-btn:hover { color: #00ffc8; }
        #imagePreview { position: absolute; bottom: 60px; left: 10px; background: rgba(0,0,0,0.7); padding: 5px; border-radius: 8px; display: none; }
        #imagePreview img { height: 80px; border-radius: 5px; }
        .remove-preview { position: absolute; top: -5px; right: -5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 10px; }
        .chat-image { max-width: 250px; border-radius: 8px; cursor: pointer; display: block; margin-top: 5px; }

        .chat-input-area { padding: 15px; background: rgba(0,0,0,0.3); display: flex; gap: 10px; border-radius: 0 0 15px 0; }
        .chat-input { flex: 1; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 25px; padding: 12px 20px; outline: none; font-size: 0.9rem; }
        .chat-input:focus { border-color: #00ffc8; background: rgba(255,255,255,0.12); }
        .chat-input::placeholder { color: #666; }
        .send-btn { background: #00ffc8; color: #000; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .send-btn:hover { transform: scale(1.05); box-shadow: 0 0 15px rgba(0, 255, 200, 0.4); }

        .empty-state { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #555; }
        
        /* Alert styling */
        .chat-error { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: rgba(255, 50, 50, 0.9); padding: 12px 25px; border-radius: 8px; z-index: 9999; display: none; backdrop-filter: blur(5px);}
    </style>
</head>
<body>

<!-- Error Alert -->
<div id="chatError" class="chat-error"></div>

<div class="top-nav">
    <a href="<?php echo $back_url; ?>" class="back-btn">
        <i class="fa fa-arrow-left me-2"></i> Back to Dashboard
    </a>
    <h5 class="mb-0"><i class="fa fa-comments me-2"></i> Messages</h5>
    <span class="role-badge role-<?php echo $role; ?>">
        <?php echo $role; ?>
    </span>
</div>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fa fa-inbox me-2"></i> Conversations
        </div>
        <div class="room-list" id="roomList">
            <!-- Rooms loaded here -->
        </div>
    </div>

    <!-- Chat Window -->
    <div class="chat-main" id="chatMain">
        <div class="empty-state" id="emptyState">
            <i class="fa fa-paper-plane fa-3x mb-3"></i>
            <h5>Select a conversation</h5>
            <small>You can only message your followers or users you follow.</small>
        </div>

        <div id="chatWindow" style="display:none; flex-direction:column; height:100%;">
            <div class="chat-header" id="chatHeader"></div>
            <div class="messages-area" id="messagesArea"></div>
            <div class="chat-input-area">
                <input type="text" class="chat-input" id="messageInput" placeholder="Type a message..." autocomplete="off">
                <button class="send-btn" id="sendBtn"><i class="fa fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<script>
let activeRoomId = null;
let pollingTimer = null;
let lastMessageId = 0;

 $(document).ready(function() {
    loadRooms();

    <?php if ($auto_open_user > 0): ?>
        startChatWith(<?php echo $auto_open_user; ?>);
    <?php endif; ?>

    // Send on click or Enter
    $('#sendBtn').click(sendMessage);
    $('#messageInput').keypress(function(e) {
        if (e.which == 13) sendMessage();
    });

    // Attach Image Button
    $('#attachBtn').click(function() {
        $('#mediaInput').click();
    });

    // Trigger preview when file is selected
    $('#mediaInput').change(function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                $('#previewImg').attr('src', ev.target.result);
                $('#imagePreview').show();
            }
            reader.readAsDataURL(file);
        }
    });
});

function removePreview() {
    $('#mediaInput').val(''); // Clear file input
    $('#imagePreview').hide();
}

function showError(msg) {
    const el = $('#chatError');
    el.text(msg).fadeIn(300);
    setTimeout(() => el.fadeOut(500), 4000);
}

function loadRooms() {
    $.get('/api/handlers/chat_handler.php', { action: 'get_rooms' }, function(res) {
        if (!res.success) return;
        let html = '';
        res.rooms.forEach(room => {
            let unread = room.unread > 0 ? `<span class="unread-badge">${room.unread}</span>` : '';
            let lastMsg = room.last_message ? (room.last_message.length > 30 ? room.last_message.substring(0, 30) + '...' : room.last_message) : 'No messages yet';
            
            html += `
            <div class="room-item ${activeRoomId == room.room_id ? 'active' : ''}" onclick="openRoom(${room.room_id}, '${room.username.replace(/'/g, "\\'")}', '${room.profile_image}')">
                <img src="${room.profile_image || 'https://cdn-icons-png.flaticon.com/512/295/295128.png'}" class="room-avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/295/295128.png'">
                <div class="room-info">
                    <div class="room-name">${room.username} ${unread}</div>
                    <div class="room-last-msg">${lastMsg}</div>
                </div>
            </div>`;
        });
        $('#roomList').html(html || '<div class="p-4 text-center text-muted small">No conversations yet</div>');
    });
}

function startChatWith(targetId) {
    $.post('/api/handlers/chat_handler.php', { action: 'start_chat', target_user_id: targetId }, function(res) {
        if (res.success) {
            loadRooms(); 
            openRoom(res.room_id, res.username, res.profile_image);
        } else {
            showError(res.error); 
        }
    });
}

function openRoom(roomId, username, avatar) {
    activeRoomId = roomId;
    lastMessageId = 0;
    removePreview(); // Clear any old image previews
    
    $('#chatHeader').html(`
        <img src="${avatar || 'https://cdn-icons-png.flaticon.com/512/295/295128.png'}" onerror="this.src='https://cdn-icons-png.flaticon.com/512/295/295128.png'">
        <div>
            <div class="fw-bold">${username || 'Loading...'}</div>
            <small class="text-success">Online</small>
        </div>
    `);

    $('#emptyState').hide();
    $('#chatWindow').css('display', 'flex');
    $('#messagesArea').html('');
    
    $('.room-item').removeClass('active');
    $('.room-item').each(function() {
        if($(this).attr('onclick') && $(this).attr('onclick').includes(`openRoom(${roomId},`)) {
            $(this).addClass('active');
            if(username === 'User') {
                username = $(this).find('.room-name').text().trim();
                $('#chatHeader .fw-bold').text(username);
            }
        }
    });

    fetchMessages();
    startPolling();
    $('#messageInput').focus();
}

function fetchMessages() {
    $.get('/api/handlers/chat_handler.php', { action: 'get_messages', room_id: activeRoomId, last_id: lastMessageId }, function(res) {
        if (!res.success) return;
        
        if (res.messages.length > 0) {
            res.messages.forEach(msg => {
                appendMessage(msg);
                lastMessageId = msg.id;
            });
        } else if (lastMessageId > 0) {
            // Polling fallback for read receipts
            $.get('/api/handlers/chat_handler.php', { action: 'get_messages', room_id: activeRoomId, last_id: (lastMessageId - 3) }, function(res2) {
                if (res2.success && res2.messages.length > 0) {
                    res2.messages.forEach(msg => {
                        $(`.msg-bubble`).filter(function() {
                            return $(this).find('.msg-text-content').text() === msg.message;
                        }).remove();
                        appendMessage(msg);
                    });
                }
            });
        }
        scrollToBottom();
    });
}

function appendMessage(msg) {
    let isSent = msg.sender_id == <?php echo $user_id; ?>;
    let bubbleClass = isSent ? 'msg-sent' : 'msg-received';
    let time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    // Render content (Text vs Image)
    let content = '';
    if (msg.media_type === 'image' && msg.media_url) {
        content += `<img src="${msg.media_url}" class="chat-image" onclick="window.open('${msg.media_url}', '_blank')">`;
    }
    if (msg.message) {
        content += `<div class="msg-text-content" style="margin-top:5px;">${msg.message}</div>`;
    }

    let html = `
    <div class="msg-bubble ${bubbleClass}">
        ${content}
        <div class="msg-time">${time} ${isSent ? (msg.is_read ? '<i class="fa fa-check-double text-primary"></i>' : '<i class="fa fa-check text-muted"></i>') : ''}</div>
    </div>`;
    
    $('#messagesArea').append(html);
}

// CHANGED TO FORMDATA TO SUPPORT FILES
function sendMessage() {
    let msg = $('#messageInput').val().trim();
    let fileInput = document.getElementById('mediaInput');
    let hasFile = fileInput.files.length > 0;

    if (!msg && !hasFile || !activeRoomId) return;

    let formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('room_id', activeRoomId);
    formData.append('message', msg);
    
    if (hasFile) {
        formData.append('media', fileInput.files[0]);
    }

    $.ajax({
        url: '/api/handlers/chat_handler.php',
        type: 'POST',
        data: formData,
        processData: false, // Required for FormData
        contentType: false, // Required for FormData
        success: function(res) {
            if (res && res.success) {
                $('#messageInput').val('');
                removePreview(); // Clear image preview
                fetchMessages(); 
                loadRooms(); 
            } else {
                showError(res.error || "Unknown error");
            }
        },
        error: function() {
            showError("Network error sending message.");
        }
    });
}

function startPolling() {
    clearInterval(pollingTimer);
    pollingTimer = setInterval(fetchMessages, 3000); 
}

function scrollToBottom() {
    const el = document.getElementById('messagesArea');
    el.scrollTop = el.scrollHeight;
}
</script>
</body>
</html>