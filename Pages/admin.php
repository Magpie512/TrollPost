<?php
session_start();
require '../includes/connect.php';

// Must be logged in AND be an admin
if ($_SESSION['isadmin'] != 1) {
    header("Location: ../index.php");
    exit;
}

$success = "";
$error = "";

// POST handler

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete a single post
    if ($action === 'delete_post') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            // Remove image file if exists
            $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            if ($post && !empty($post['image_path']) && file_exists('../' . $post['image_path'])) {
                unlink('../' . $post['image_path']);
            }
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $success = "Post deleted.";
        }
    }

    // Delete a user (cascades to their posts via FK (foreign key))
    if ($action === 'delete_user') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        // Prevent admin from deleting themselves
        if ($targetId > 0 && $targetId !== (int) $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $success = "User and all their posts deleted.";
        } else {
            $error = "You cannot delete your own admin account.";
        }
    }

    // Redirect to avoid form resubmission, preserve search query if any
    $qs = isset($_POST['search']) ? '?search=' . urlencode($_POST['search']) : '';
    header("Location: admin.php" . $qs);
    exit;
}

// Search / Load users functionality

$search = trim($_GET['search'] ?? '');
$viewUserId = isset($_GET['user']) ? (int) $_GET['user'] : null;

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at, isadmin FROM users
                           WHERE username LIKE ? OR email LIKE ?
                           ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at, isadmin FROM users ORDER BY created_at DESC");
    $stmt->execute();
}
$users = $stmt->fetchAll();

// Load posts for a specific user 
$viewedUser = null;
$viewedPosts = [];

if ($viewUserId) {
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$viewUserId]);
    $viewedUser = $stmt->fetch();

    if ($viewedUser) {
        $stmt = $pdo->prepare("SELECT id, content, image_path, created_at FROM posts WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$viewUserId]);
        $viewedPosts = $stmt->fetchAll();
    }
}

// Stats 
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrollPost – Admin Panel</title>
    <link rel="stylesheet" href="../styles/Normalize.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/gob.png">
    <link rel="stylesheet" href="../styles/admin.css">
</head>

<body>

    <!-- Top Bar -->
    <div class="topbar">
        <img src="../img/gob.png" alt="TrollPost">
        <h1>⚔ Admin Sanctum</h1> 
        <div class="topbar-links">
            <a href="../index.php">← Back to Site</a>
            <a href="logout.php">Log Out</a>
        </div>
    </div>

    <div class="wrap">

        <!-- MacOS Sidebar, wait no its sidecar  -->
        <aside class="sidebar">

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-num"><?= $totalUsers ?></div>
                    <div class="stat-label">Users</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><?= $totalPosts ?></div>
                    <div class="stat-label">Posts</div>
                </div>
            </div>

            <!-- Search -->
            <div class="search-wrap">
                <form method="GET" action="admin.php">
                    <input type="text" name="search" placeholder="Search username or email…"
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">Scry</button>
                </form>
                <?php if ($search !== ''): ?>
                    <div style="margin-top:6px; font-size:11px; color:var(--text-dim);">
                        <?= count($users) ?> result(s) for
                        "<strong style="color:var(--gold-dim)"><?= htmlspecialchars($search) ?></strong>"
                        — <a href="admin.php" style="color:var(--text-dim);">clear</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sidebar-title">
                <?= $search !== '' ? 'Search Results' : 'All Adventurers' ?>
            </div>

            <!-- User List -->
            <div class="user-list">
                <?php if (empty($users)): ?>
                    <div style="padding:16px 12px; color:var(--text-dim); font-style:italic; font-size:13px;">
                        No users found.
                    </div>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $isActive = ($viewUserId === (int) $u['id']);
                        $initial = strtoupper(substr($u['username'], 0, 1));
                        $href = 'admin.php?user=' . $u['id'] . ($search !== '' ? '&search=' . urlencode($search) : '');
                        ?>
                        <a href="<?= $href ?>" class="user-row <?= $isActive ? 'active' : '' ?>">
                            <div class="user-avatar"><?= $initial ?></div>
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                            <?php if ($u['isadmin'] == 1): ?>
                                <span class="badge-admin">Admin</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </aside>

        <!-- Main Panel -->
        <main class="main">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($viewedUser): ?>

                <!-- User Detail -->
                <div class="user-detail-header">
                    <div class="detail-avatar">
                        <?= strtoupper(substr($viewedUser['username'], 0, 1)) ?>
                    </div>
                    <div class="detail-info">
                        <h2><?= htmlspecialchars($viewedUser['username']) ?></h2>
                        <p><?= htmlspecialchars($viewedUser['email']) ?></p>
                        <p class="detail-meta">
                            <?= count($viewedPosts) ?> post(s) &nbsp;·&nbsp; ID #<?= $viewedUser['id'] ?>
                        </p>
                    </div>

                    <?php if ($viewedUser['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="admin.php"
                            onsubmit="return confirm('Delete <?= htmlspecialchars($viewedUser['username']) ?> and ALL their posts? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $viewedUser['id'] ?>">
                            <?php if ($search !== ''): ?>
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn-delete-user">⚔ Delete User</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Posts -->
                <div class="section-title">
                    Posts by <?= htmlspecialchars($viewedUser['username']) ?>
                </div>

                <?php if (empty($viewedPosts)): ?>
                    <div class="no-posts">This user has not posted anything yet.</div>
                <?php else: ?>
                    <?php foreach ($viewedPosts as $p): ?>
                        <div class="post-card">
                            <?php if (!empty($p['image_path'])): ?>
                                <img src="../<?= htmlspecialchars($p['image_path']) ?>" alt="Post image" class="post-img">
                            <?php endif; ?>
                            <div class="post-body">
                                <div class="post-content"><?= htmlspecialchars($p['content']) ?></div>
                                <div class="post-meta-row"><?= $p['created_at'] ?> &nbsp;·&nbsp; #<?= $p['id'] ?></div>
                            </div>
                            <form method="POST" action="admin.php" onsubmit="return confirm('Delete this post?')">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="user_id_return" value="<?= $viewedUser['id'] ?>">
                                <?php if ($search !== ''): ?>
                                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn-delete-post">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>

                <!-- Default welcome state -->
                <div class="welcome">
                    <h2>Select a User</h2>
                    <p>Choose an adventurer from the left to inspect their posts and account.</p>
                </div>

            <?php endif; ?>

        </main>
    </div>

</body>

</html>