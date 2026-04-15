<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

 $user_id = $_SESSION['user_id'];
 $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

if (empty($username)) {
    include 'db_connection.php';
    $user_sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $username = $user_data['username'];
        $_SESSION['username'] = $username;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Existing Navbar Styles */
        .search-container { position: relative; }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 0 0 5px 5px; max-height: 300px; overflow-y: auto; z-index: 1000; display: none; }
        .search-results.show { display: block; }
        .notification-item { padding: 10px; border-bottom: 1px solid #eee; }
        .notification-item:last-child { border-bottom: none; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 11px; }

        /* NEW: Feed Styles (Matching your glassmorphism theme) */
        body { background: #f8f9fa; }
        .feed-container { max-width: 600px; margin: 30px auto; }
        .create-post-box, .post-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .post-avatar {
            width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef;
        }
        .post-actions .btn-light {
            background: #f8f9fa; border: none; color: #6c757d; font-weight: 500;
        }
        .post-actions .btn-light:hover { background: #e9ecef; color: #000; }
        .post-actions .btn-light.active { color: #dc3545; }
        .post-actions .btn-light.active i { font-weight: 900; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-sm navbar-light bg-success">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-weight:bold; color:white;">Dashboard</a>
            <span style="color:white; font-weight:bold;">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavId">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="collapsibleNavId">
                <ul class="navbar-nav m-auto mt-2 mt-lg-0"></ul>
                <div class="d-flex align-items-center gap-2">   
                    <a href="chat.php" class="btn btn-light my-2 my-sm-0" style="font-weight:bolder;color:purple;"><i class="fa fa-comments"></i></a>

                    <div class="search-container">
                        <button class="btn btn-light" type="button" id="searchToggle"><i class="fa fa-search"></i></button>
                        <div class="search-form" style="display: none; position: absolute; top: 40px; right: 0; background: white; padding: 10px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; z-index: 1000;">
                            <form method="GET" id="searchForm">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search users..." id="searchInput">
                                    <button class="btn btn-success" type="submit">Search</button>
                                </div>
                            </form>
                            <div id="searchResults" class="search-results"></div>
                        </div>
                    </div>

                    <div class="position-relative">
                        <button class="btn btn-light position-relative" type="button" id="notificationsToggle">
                            <i class="fa fa-bell"></i>
                            <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
                        </button>
                        <div id="notificationsContainer" style="display: none; position: absolute; top: 40px; right: 0; background: white; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; max-height: 400px; overflow-y: auto; z-index: 1000;">
                            <div class="p-2 border-bottom d-flex justify-content-between">
                                <h5 class="mb-0">Notifications</h5>
                                <button id="markAllReadBtn" class="btn btn-sm btn-outline-secondary">Mark all read</button>
                            </div>
                            <div id="notificationsList"></div>
                        </div>
                    </div>
                     
                    <a href="profile.php" class="btn btn-light my-2 my-sm-0" style="font-weight:bolder;color:orange;"><i class="fa fa-user-circle"></i></a>

                    <button class="btn btn-light my-2 my-sm-0" type="button" data-bs-toggle="modal" data-bs-target="#logoutModal" style="font-weight:bolder;color:red;">
                        <i class="fa fa-sign-out"></i>                    
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Confirm Logout</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Are you sure you want to logout?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FEED OR MAIN CONTENT AREA -->
    <div class="container feed-container">
        
        <!-- Create Post Section -->
        <div class="create-post-box">
            <form id="createPostForm" class="d-flex flex-column gap-2">
                <textarea class="form-control border-0" id="postContent" rows="3" placeholder="What's on your mind, <?php echo htmlspecialchars($username); ?>?" required style="resize: none; box-shadow: none;"></textarea>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Post to your followers</small>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa fa-paper-plane"></i> Post
                    </button>
                </div>
            </form>
        </div>

        <!-- Feed Actions -->
        <div class="d-flex justify-content-end mb-3">
            <button id="refreshFeedBtn" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-refresh"></i> Refresh Feed
            </button>
        </div>

        <!-- Content Container (Loaded via AJAX) -->
        <div id="content-container">
            <div class="text-center text-muted mt-5">
                <div class="spinner-border text-success" role="status"></div>
                <p class="mt-2">Loading feed...</p>
            </div>
        </div>
        
    </div>

    <script>
        $(document).ready(function(){
            
            // --- FEED LOGIC ---
            
            function loadFeed() {
                $('#content-container').load('load_feed.php');
            }

            // Create Post
            $('#createPostForm').submit(function(e){
                e.preventDefault();
                let content = $('#postContent').val().trim();
                if(!content) return;

                let btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Posting...');

                $.post('create_post.php', { content: content }, function(res){
                    res = JSON.parse(res);
                    if(res.success) {
                        $('#postContent').val(''); // clear textarea
                        $('#content-container').prepend(res.html); // add new post to top
                    } else {
                        alert(res.error || 'Failed to create post.');
                    }
                    btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Post');
                });
            });

            // Like Post
            $(document).on('click', '.like-btn', function(){
                let btn = $(this);
                let postCard = btn.closest('.post-card');
                let post_id = postCard.data('post-id');
                let countSpan = btn.find('span');
                let heartIcon = btn.find('i');

                $.post('like_handler.php', { post_id: post_id }, function(res){
                    res = JSON.parse(res);
                    if(res.success) {
                        countSpan.text(res.new_count);
                        if(res.action === 'liked') {
                            btn.addClass('active');
                            heartIcon.removeClass('fa-heart-o').addClass('fa-heart text-danger');
                        } else {
                            btn.removeClass('active');
                            heartIcon.removeClass('fa-heart text-danger').addClass('fa-heart-o');
                        }
                    }
                });
            });

            // Delete Post
            $(document).on('click', '.delete-post-btn', function(){
                if(!confirm('Are you sure you want to delete this post?')) return;
                let postCard = $(this).closest('.post-card');
                let post_id = postCard.data('post-id');

                $.post('delete_post.php', { post_id: post_id }, function(res){
                    res = JSON.parse(res);
                    if(res.success) {
                        postCard.fadeOut(300, function(){ $(this).remove(); });
                    } else {
                        alert(res.error || 'Failed to delete.');
                    }
                });
            });

            // Refresh Feed Button
            $('#refreshFeedBtn').click(function(){
                loadFeed();
            });

            // Initial Feed Load
            loadFeed();


            // --- EXISTING NAVBAR LOGIC (Tweaked for new mark-all button) ---
            
            $('#searchToggle').click(function(e){ e.stopPropagation(); $('.search-form').toggle(); $('#notificationsContainer').hide(); if($('.search-form').is(':visible')) { $('#searchInput').focus(); } });
            $('#notificationsToggle').click(function(e){ e.stopPropagation(); $('#notificationsContainer').toggle(); $('.search-form').hide(); if($('#notificationsContainer').is(':visible')) { loadNotifications(); } });
            $(document).click(function(){ $('.search-form').hide(); $('#notificationsContainer').hide(); });
            $('.search-form, #notificationsContainer').click(function(e){ e.stopPropagation(); });
            
            $('#searchForm').submit(function(e){
                e.preventDefault();
                const searchTerm = $('#searchInput').val().trim();
                if(searchTerm) { $.get('search.php', { search: searchTerm }, function(response) { $('#searchResults').html(response).addClass('show'); }); }
            });
            
            function loadNotifications() { $.get('notifications.php', function(response) { $('#notificationsList').html(response); }); }
            
            // Mark all read button
            $('#markAllReadBtn').click(function(){
                $.post('notifications.php', { action: 'mark_all_read' }, function(){
                    loadNotifications();
                    $('#notificationBadge').hide();
                });
            });

            // Mark single read
            $(document).on('click', '.mark-read', function(e){
                e.stopPropagation();
                let notif_id = $(this).closest('.notification-item').data('notif-id');
                $.post('notifications.php', { action: 'mark_read', notif_id: notif_id }, function(){
                    $('[data-notif-id="'+notif_id+'"]').removeClass('unread');
                    $('[data-notif-id="'+notif_id+'"]').find('.mark-read').remove();
                    // update badge
                    $.get('notifications.php', { count_only: true }, function(c){ if(c>0) $('#notificationBadge').text(c).show(); else $('#notificationBadge').hide(); });
                });
            });

            // Chat Requests
            $(document).on('click', '.acceptChat', function(){ let sender_id = $(this).data('sender-id'); $.post('notifications.php', { action: 'accept_chat', sender_id: sender_id }, function(res){ let d=JSON.parse(res); if(d.success) { alert('Accepted!'); loadNotifications(); window.location.href='chat.php?user_id='+sender_id; } }); });
            $(document).on('click', '.rejectChat', function(){ let sender_id = $(this).data('sender-id'); $.post('notifications.php', { action: 'reject_chat', sender_id: sender_id }, function(res){ let d=JSON.parse(res); if(d.success) { loadNotifications(); } }); });
            
            // Load notification count on page load
            $.get('notifications.php', { count_only: true }, function(response) { if(response > 0) { $('#notificationBadge').text(response).show(); } });
        });
    </script>
</body>
</html>