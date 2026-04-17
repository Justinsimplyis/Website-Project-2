<?php
session_start();
include 'C:/Users/User/Documents/GitHub/Website-Project-2/database/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/auth/login.php");
    exit();
}

 $logged_in_id = $_SESSION['user_id'];
 $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
 $view_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($view_id <= 0) { echo "Invalid user ID."; exit(); }

if ($logged_in_id === $view_id) {
    header("Location: " . ($role === 'admin' ? 'C:/Users/User/Documents/GitHub/Website-Project-2/dashboards/admin/admin_dashboard.php' : 'C:/Users/User/Documents/GitHub/Website-Project-2/dashboards/users/dashboard.php'));
    exit();
}

// Fetch user info
 $sql = "SELECT u.id, u.username, p.full_name, p.age, p.gender, p.bio, p.relationship_status, p.profile_image FROM users u LEFT JOIN users_profile p ON u.id = p.user_id WHERE u.id = ?";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("i", $view_id);
 $stmt->execute();
if ($stmt->get_result()->num_rows === 0) { echo "User not found."; exit(); }
 $user = $stmt->get_result()->fetch_assoc();

// Check following status
 $stmt_follow = $conn->prepare("SELECT * FROM followers WHERE follower_id = ? AND followed_id = ?");
 $stmt_follow->bind_param("ii", $logged_in_id, $view_id);
 $stmt_follow->execute();
 $is_following = $stmt_follow->get_result()->num_rows > 0;

// Fetch Counts
 $follower_count = $conn->prepare("SELECT COUNT(*) as total FROM followers WHERE followed_id = ?");
 $follower_count->bind_param("i", $view_id); $follower_count->execute(); $fc = $follower_count->get_result()->fetch_assoc()['total'];

 $following_count = $conn->prepare("SELECT COUNT(*) as total FROM followers WHERE follower_id = ?");
 $following_count->bind_param("i", $view_id); $following_count->execute(); $fgc = $following_count->get_result()->fetch_assoc()['total'];

// Fetch Lists for Modals
 $fl_sql = $conn->prepare("SELECT u.id, u.username, p.profile_image FROM followers f JOIN users u ON f.follower_id = u.id LEFT JOIN users_profile p ON u.id = p.user_id WHERE f.followed_id = ?");
 $fl_sql->bind_param("i", $view_id); $fl_sql->execute(); $followers_list = $fl_sql->get_result()->fetch_all(MYSQLI_ASSOC);

 $fgl_sql = $conn->prepare("SELECT u.id, u.username, p.profile_image FROM followers f JOIN users u ON f.followed_id = u.id LEFT JOIN users_profile p ON u.id = p.user_id WHERE f.follower_id = ?");
 $fgl_sql->bind_param("i", $view_id); $fgl_sql->execute(); $following_list = $fgl_sql->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Posts
 $posts_sql = $conn->prepare("SELECT p.*, u.username, up.profile_image, COUNT(DISTINCT l.id) as like_count, COUNT(DISTINCT c.id) as comment_count, MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) as user_has_liked FROM posts p JOIN users u ON p.user_id = u.id LEFT JOIN users_profile up ON u.id = up.user_id LEFT JOIN post_likes l ON p.id = l.post_id LEFT JOIN post_comments c ON p.id = c.post_id WHERE p.is_deleted = 0 AND p.user_id = ? GROUP BY p.id ORDER BY p.created_at DESC");
 $posts_sql->bind_param("ii", $logged_in_id, $view_id);
 $posts_sql->execute();
 $user_posts = $posts_sql->get_result()->fetch_all(MYSQLI_ASSOC);

 $conn->close();

function getTimeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    elseif ($diff < 3600) return floor($diff / 60) . 'm ago';
    elseif ($diff < 86400) return floor($diff / 3600) . 'h ago';
    elseif ($diff < 604800) return floor($diff / 86400) . 'd ago';
    else return date("M j, Y", strtotime($datetime));
}

 $dashboard_page = ($role === 'admin') ? 'C:/Users/User/Documents/GitHub/Website-Project-2/dashboards/admin/admin_dashboard.php' : 'C:/Users/User/Documents/GitHub/Website-Project-2/dashboards/users/dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root { --primary-glow: #00ffc8; --bg-dark: #0f2027; }
        body { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: white; font-family: 'Inter', 'Segoe UI', sans-serif; min-height: 100vh; }

        /* Modern Navbar */
        .navbar { background: rgba(15, 32, 39, 0.9) !important; backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .btn-back { color: #00ffc8 !important; border: none !important; background: transparent !important; font-weight: 600; }
        .btn-back:hover { color: white !important; }

        /* CSS Grid Layout */
        .profile-grid-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
            max-width: 960px;
            margin: 30px auto;
            padding: 0 15px;
        }

        /* Right Sidebar */
        .sidebar-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 25px;
            position: sticky;
            top: 80px;
        }
        .profile-avatar-lg {
            width: 110px; height: 110px; border-radius: 50%; object-fit: cover;
            border: 4px solid rgba(0, 255, 200, 0.3);
            box-shadow: 0 0 25px rgba(0, 255, 200, 0.15);
            margin: 0 auto; display: block;
        }
        .sidebar-username { font-size: 1.4rem; font-weight: 700; margin-top: 15px; text-align: center; }
        
        /* Modern Stats Row */
        .stats-row { display: flex; justify-content: center; gap: 25px; margin: 20px 0; }
        .stat-item { text-align: center; cursor: pointer; transition: opacity 0.2s; }
        .stat-item:hover { opacity: 0.8; }
        .stat-item .num { display: block; font-weight: 700; font-size: 1.1rem; color: white; }
        .stat-item .label { font-size: 0.85rem; color: rgba(255,255,255,0.6); }

        /* Modern Action Buttons */
        .action-btn { width: 100%; border-radius: 50px; padding: 10px; font-weight: 600; font-size: 0.95rem; border: none; transition: all 0.2s; }
        .btn-follow-active { background: transparent; border: 1px solid rgba(255,255,255,0.3) !important; color: white !important; }
        .btn-follow-active:hover { border-color: #ff4757 !important; color: #ff4757 !important; background: rgba(255,71,87,0.1) !important; }
        .btn-follow-default { background: var(--primary-glow); color: var(--bg-dark); }
        .btn-follow-default:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Sidebar Info */
        .sidebar-info { margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.9rem; color: rgba(255,255,255,0.8); text-align: center; line-height: 1.6; }
        .sidebar-info .bio-text { font-style: italic; margin-bottom: 15px; word-wrap: break-word; }
        .info-pill { background: rgba(255,255,255,0.06); padding: 4px 10px; border-radius: 20px; display: inline-block; margin: 3px; font-size: 0.8rem; }

        /* Main Feed (Left Column) */
        .feed-header { margin-bottom: 20px; font-size: 1.2rem; font-weight: 600; color: rgba(255,255,255,0.8); }
        
        /* Post Cards */
        .post-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .post-card:hover { transform: translateY(-2px); background: rgba(255,255,255,0.07); }
        .post-header img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
        .post-content { margin: 15px 0; line-height: 1.5; color: rgba(255,255,255,0.9); word-wrap: break-word; }
        
        .post-actions { display: flex; gap: 15px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px; margin-top: 10px; }
        .action-btn-post { background: transparent; border: none; color: rgba(255,255,255,0.6); font-size: 0.9rem; display: flex; align-items: center; gap: 6px; transition: all 0.2s; padding: 5px 10px; border-radius: 8px;}
        .action-btn-post:hover { background: rgba(255,255,255,0.05); color: white; }
        .action-btn-post.liked { color: #ff4757; }
        .action-btn-post i { font-size: 1.1rem; transition: transform 0.2s; }
        .action-btn-post:hover i { transform: scale(1.2); }

        /* Modals */
        .modal-content { background: #1a2a30; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; }
        .user-list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: 12px; transition: background 0.2s; }
        .user-list-item:hover { background: rgba(255,255,255,0.05); }
        .user-list-item img { width: 42px; height: 42px; border-radius: 50%; }

        /* Empty State */
        .empty-state { text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.4); }

        /* Responsive Breakpoint */
        @media (max-width: 991px) {
            .profile-grid-container { grid-template-columns: 1fr; max-width: 600px; }
            .sidebar-card { position: static; order: -1; margin-bottom: 20px; }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-sm sticky-top">
        <div class="container-fluid px-3">
            <a class="navbar-brand btn-back" href="<?php echo $dashboard_page; ?>">
                <i class="fa fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </nav>

    <div class="profile-grid-container">
        
        <!-- LEFT: Main Feed Column -->
        <main class="main-column">
            <div class="feed-header">Posts</div>
            
            <?php if (count($user_posts) > 0): ?>
                <?php foreach ($user_posts as $post): 
                    $avatar = !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/295/295128.png';
                    $time_ago = getTimeAgo($post['created_at']);
                    $is_liked = $post['user_has_liked'];
                    $is_mod = ($role === 'admin' || $role === 'moderator');
                ?>
                    <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo $avatar; ?>" alt="avatar">
                                <div>
                                    <div class="fw-bold" style="font-size: 0.95rem;"><?php echo htmlspecialchars($post['username']); ?></div>
                                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.4);"><?php echo $time_ago; ?></div>
                                </div>
                            </div>
                            <?php if ($is_mod): ?>
                                <button class="action-btn-post text-danger delete-post-btn" title="Delete"><i class="fa fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                        
                        <div class="post-actions">
                            <button class="action-btn-post like-btn <?php echo $is_liked ? 'liked' : ''; ?>">
                                <i class="fa <?php echo $is_liked ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                                <span><?php echo $post['like_count']; ?></span>
                            </button>
                            <div class="action-btn-post disabled" style="cursor: default;">
                                <i class="fa fa-comment-o"></i>
                                <span><?php echo $post['comment_count']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state glass-box">
                    <i class="fa fa-pencil-square-o fa-3x mb-3"></i>
                    <p>No posts yet.</p>
                </div>
            <?php endif; ?>
        </main>

        <!-- RIGHT: Sidebar Column -->
        <aside class="sidebar-column">
            <div class="sidebar-card">
                <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/295/295128.png'; ?>" class="profile-avatar-lg" alt="profile">
                <h1 class="sidebar-username"><?php echo htmlspecialchars($user['username']); ?></h1>
                
                <?php if (!empty($user['full_name'])): ?>
                    <div class="text-center" style="color: rgba(255,255,255,0.5); font-size: 0.9rem;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <?php endif; ?>

                <!-- Modern Stats -->
                <div class="stats-row">
                    <div class="stat-item" data-bs-toggle="modal" data-bs-target="#followersModal">
                        <span class="num"><?php echo $fc; ?></span>
                        <span class="label">Followers</span>
                    </div>
                    <div class="stat-item" data-bs-toggle="modal" data-bs-target="#followingModal">
                        <span class="num"><?php echo $fgc; ?></span>
                        <span class="label">Following</span>
                    </div>
                </div>

                <?php if ($logged_in_id !== $view_id): ?>
                    <form method="post" action="api/handlers/follow_unfollow_handler.php" class="mb-2">
                        <input type="hidden" name="user_id" value="<?php echo $view_id; ?>">
                        <?php if ($is_following): ?>
                            <button type="submit" name="unfollow" class="action-btn btn-follow-active"><i class="fa fa-user-times me-2"></i>Following</button>
                        <?php else: ?>
                            <button type="submit" name="follow" class="action-btn btn-follow-default"><i class="fa fa-user-plus me-2"></i>Follow</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>

                <div class="sidebar-info">
                    <?php if (!empty($user['bio'])): ?>
                        <div class="bio-text">"<?php echo htmlspecialchars($user['bio']); ?>"</div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <?php if (!empty($user['relationship_status'])): ?>
                            <span class="info-pill"><i class="fa fa-heart me-1"></i> <?php echo htmlspecialchars($user['relationship_status']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($user['gender'])): ?>
                            <span class="info-pill"><i class="fa fa-venus-mars me-1"></i> <?php echo htmlspecialchars($user['gender']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($user['age'])): ?>
                            <span class="info-pill"><i class="fa fa-birthday-cake me-1"></i> <?php echo htmlspecialchars($user['age']); ?> yrs</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="followersModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Followers</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body pt-2">
        <?php if(count($followers_list) > 0): foreach($followers_list as $u): ?>
            <div class="user-list-item"><div class="d-flex align-items-center gap-3"><img src="<?php echo !empty($u['profile_image']) ? htmlspecialchars($u['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/295/295128.png'; ?>"><strong><?php echo htmlspecialchars($u['username']); ?></strong></div><a href="profile_view.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">View</a></div>
        <?php endforeach; else: ?> <div class="empty-state"><p>No followers yet.</p></div> <?php endif; ?>
    </div></div></div></div>

    <div class="modal fade" id="followingModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Following</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body pt-2">
        <?php if(count($following_list) > 0): foreach($following_list as $u): ?>
            <div class="user-list-item"><div class="d-flex align-items-center gap-3"><img src="<?php echo !empty($u['profile_image']) ? htmlspecialchars($u['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/295/295128.png'; ?>"><strong><?php echo htmlspecialchars($u['username']); ?></strong></div><a href="profile_view.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">View</a></div>
        <?php endforeach; else: ?> <div class="empty-state"><p>Not following anyone.</p></div> <?php endif; ?>
    </div></div></div></div>

    <script>
        $(document).ready(function(){
            // Like Animation & Logic
            $(document).on('click', '.like-btn', function(){
                let btn = $(this);
                let post_id = btn.closest('.post-card').data('post-id');
                let icon = btn.find('i');
                let count = btn.find('span');

                $.post('like_handler.php', { post_id: post_id }, function(res){
                    res = JSON.parse(res);
                    if(res.success) {
                        count.text(res.new_count);
                        if(res.action === 'liked') {
                            btn.addClass('liked');
                            icon.removeClass('fa-heart-o').addClass('fa-heart');
                            icon.css('transform', 'scale(1.3)');
                            setTimeout(() => icon.css('transform', 'scale(1)'), 200);
                        } else {
                            btn.removeClass('liked');
                            icon.removeClass('fa-heart').addClass('fa-heart-o');
                        }
                    }
                });
            });

            // Delete Post
            $(document).on('click', '.delete-post-btn', function(e){
                e.stopPropagation();
                if(!confirm('Delete this post? This cannot be undone.')) return;
                let card = $(this).closest('.post-card');
                $.post('delete_post.php', { post_id: card.data('post-id') }, function(res){
                    res = JSON.parse(res);
                    if(res.success) card.fadeOut(300, function(){ $(this).remove(); });
                });
            });
        });
    </script>
</body>
</html>