<?php
session_start();
require '../includes/connect.php';
require '../includes/sanitize.php';

// Must be logged in AND be an admin
if ($_SESSION['isadmin'] != 1) {
    header("Location: /~Mars200561234/TrollPost/index.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete a single post
    if ($action === 'delete_post') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId > 0) {
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

    // Delete a user (cascades to their posts via FK)
    if ($action === 'delete_user') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId > 0 && $targetId !== (int) $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $success = "User and all their posts deleted.";
        } else {
            $error = "You cannot delete your own admin account.";
        }
    }

    $qs = isset($_POST['search']) ? '?search=' . urlencode($_POST['search']) : '';
    header("Location: pages/admin.php" . $qs);
    exit;
}

$search     = sanitizeSearch($_GET['search'] ?? '');
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

$viewedUser  = null;
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
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/gob.png">
    <style>
        :root {
            --bg: #8a744e;
            --panel: #3a2c18;
            --panel2: #46351c;
            --border: #8a5e20;
            --gold: #e2b45a;
            --gold-lt: #f6d47a;
            --gold-dim: #b08432;
            --red: #b83a3a;
            --red-lt: #e05a5a;
            --text: #f6edd8;
            --text-dim: #c8b890;
            --green: #4c8f4c;
            --green-lt: #78c878;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: 'Crimson Text', Georgia, serif; font-size: 15px; min-height: 100vh; }

        .topbar { background: linear-gradient(to bottom, #2a1e08, #0d0a06); border-bottom: 2px solid var(--gold-dim); padding: 12px 24px; display: flex; align-items: center; gap: 16px; position: sticky; top: 0; z-index: 100; }
        .topbar img { height: 36px; image-rendering: pixelated; }
        .topbar h1 { font-family: 'Cinzel', serif; font-size: 1.1rem; color: var(--gold-lt); letter-spacing: 3px; text-transform: uppercase; text-shadow: 0 0 12px rgba(200,148,42,0.4); flex: 1; }
        .topbar-links { display: flex; gap: 10px; }
        .topbar-links a { color: var(--text-dim); text-decoration: none; font-size: 12px; font-family: 'Cinzel', serif; letter-spacing: 1px; text-transform: uppercase; padding: 4px 10px; border: 1px solid var(--border); transition: all 0.2s; }
        .topbar-links a:hover { color: var(--gold-lt); border-color: var(--gold-dim); }

        .wrap { display: grid; grid-template-columns: 300px 1fr; min-height: calc(100vh - 62px); }

        .sidebar { background: var(--panel); border-right: 2px solid var(--border); display: flex; flex-direction: column; }
        .stats-row { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid var(--border); }
        .stat-box { padding: 16px 12px; text-align: center; border-right: 1px solid var(--border); }
        .stat-box:last-child { border-right: none; }
        .stat-num { font-family: 'Cinzel', serif; font-size: 1.8rem; color: var(--gold-lt); line-height: 1; }
        .stat-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }

        .search-wrap { padding: 14px 12px; border-bottom: 1px solid var(--border); }
        .search-wrap form { display: flex; gap: 6px; }
        .search-wrap input { flex: 1; background: var(--bg); border: 1px solid var(--border); color: var(--text); font-family: 'Crimson Text', serif; font-size: 14px; padding: 6px 10px; outline: none; transition: border-color 0.2s; }
        .search-wrap input:focus { border-color: var(--gold-dim); }
        .search-wrap input::placeholder { color: var(--text-dim); font-style: italic; }
        .btn-search { background: linear-gradient(to bottom, var(--gold), var(--gold-dim)); border: none; color: #1a0e00; font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; padding: 6px 12px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: opacity 0.2s; }
        .btn-search:hover { opacity: 0.85; }

        .sidebar-title { padding: 10px 12px 6px; font-family: 'Cinzel', serif; font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; }
        .user-list { overflow-y: auto; flex: 1; }
        .user-row { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-bottom: 1px solid var(--border); cursor: pointer; text-decoration: none; color: var(--text); transition: background 0.15s; position: relative; }
        .user-row:hover { background: var(--panel2); }
        .user-row.active { background: var(--panel2); border-left: 3px solid var(--gold); }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-dim), var(--border)); display: flex; align-items: center; justify-content: center; font-family: 'Cinzel', serif; font-size: 13px; color: var(--gold-lt); flex-shrink: 0; border: 1px solid var(--border); }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-family: 'Cinzel', serif; font-size: 12px; color: var(--gold-lt); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: 11px; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge-admin { font-size: 9px; font-family: 'Cinzel', serif; background: var(--gold-dim); color: #1a0e00; padding: 1px 5px; text-transform: uppercase; letter-spacing: 1px; flex-shrink: 0; }

        .main { background: var(--bg); padding: 24px; overflow-y: auto; }
        .alert { padding: 10px 14px; margin-bottom: 18px; font-size: 13px; border-left: 4px solid; }
        .alert-success { background: #0d200d; border-color: var(--green-lt); color: #7fcf7f; }
        .alert-error { background: #200d0d; border-color: var(--red-lt); color: #cf7f7f; }

        .welcome { text-align: center; padding: 60px 20px; color: var(--text-dim); }
        .welcome h2 { font-family: 'Cinzel', serif; font-size: 1.4rem; color: var(--gold-dim); margin-bottom: 10px; }

        .user-detail-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .detail-avatar { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-dim), var(--border)); display: flex; align-items: center; justify-content: center; font-family: 'Cinzel', serif; font-size: 22px; color: var(--gold-lt); border: 2px solid var(--gold-dim); flex-shrink: 0; }
        .detail-info h2 { font-family: 'Cinzel', serif; font-size: 1.3rem; color: var(--gold-lt); }
        .detail-info p { font-size: 13px; color: var(--text-dim); margin-top: 2px; }
        .detail-meta { font-size: 12px; color: var(--text-dim); margin-top: 4px; }
        .btn-delete-user { margin-left: auto; background: linear-gradient(to bottom, var(--red-lt), var(--red)); border: 1px solid #6b1010; color: #fff; font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; padding: 8px 16px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: opacity 0.2s; flex-shrink: 0; }
        .btn-delete-user:hover { opacity: 0.85; }

        .section-title { font-family: 'Cinzel', serif; font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; }
        .post-card { background: var(--panel); border: 1px solid var(--border); border-left: 3px solid var(--gold-dim); padding: 12px 14px; margin-bottom: 10px; display: flex; gap: 12px; align-items: flex-start; }
        .post-body { flex: 1; min-width: 0; }
        .post-content { font-size: 14px; color: var(--text); line-height: 1.5; word-break: break-word; margin-bottom: 6px; }
        .post-img { max-width: 120px; max-height: 80px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; }
        .post-meta-row { font-size: 11px; color: var(--text-dim); font-family: 'Cinzel', serif; }
        .btn-delete-post { background: none; border: 1px solid var(--red); color: var(--red-lt); font-family: 'Cinzel', serif; font-size: 10px; padding: 4px 10px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; flex-shrink: 0; align-self: center; transition: all 0.2s; }
        .btn-delete-post:hover { background: var(--red); color: #fff; }
        .no-posts { color: var(--text-dim); font-style: italic; font-size: 13px; padding: 20px 0; text-align: center; }

        @media (max-width: 768px) {
            .wrap { grid-template-columns: 1fr; }
            .sidebar { border-right: none; border-bottom: 2px solid var(--border); max-height: 50vh; }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <img src="../img/gob.png" alt="TrollPost">
        <h1>⚔ Admin Sanctum</h1>
        <div class="topbar-links">
            <a href="index.php"> Back to Site</a>
            <a href="pages/logout.php">Log Out</a>
        </div>
    </div>

    <div class="wrap">

        <aside class="sidebar">

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

            <div class="search-wrap">
                <form method="GET" action="pages/admin.php">
                    <input type="text" name="search" placeholder="Search username or email…"
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">Scry</button>
                </form>
                <?php if ($search !== ''): ?>
                    <div style="margin-top:6px; font-size:11px; color:var(--text-dim);">
                        <?= count($users) ?> result(s) for
                        "<strong style="color:var(--gold-dim)"><?= htmlspecialchars($search) ?></strong>"
                        — <a href="pages/admin.php" style="color:var(--text-dim);">clear</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sidebar-title">
                <?= $search !== '' ? 'Search Results' : 'All Adventurers' ?>
            </div>

            <div class="user-list">
                <?php if (empty($users)): ?>
                    <div style="padding:16px 12px; color:var(--text-dim); font-style:italic; font-size:13px;">No users found.</div>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $isActive = ($viewUserId === (int) $u['id']);
                        $initial  = strtoupper(substr($u['username'], 0, 1));
                        $href     = 'pages/admin.php?user=' . $u['id'] . ($search !== '' ? '&search=' . urlencode($search) : '');
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

        <main class="main">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($viewedUser): ?>

                <div class="user-detail-header">
                    <div class="detail-avatar"><?= strtoupper(substr($viewedUser['username'], 0, 1)) ?></div>
                    <div class="detail-info">
                        <h2><?= htmlspecialchars($viewedUser['username']) ?></h2>
                        <p><?= htmlspecialchars($viewedUser['email']) ?></p>
                        <p class="detail-meta"><?= count($viewedPosts) ?> post(s) &nbsp;·&nbsp; ID #<?= $viewedUser['id'] ?></p>
                    </div>

                    <?php if ($viewedUser['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="pages/admin.php"
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

                <div class="section-title">Posts by <?= htmlspecialchars($viewedUser['username']) ?></div>

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
                            <form method="POST" action="pages/admin.php" onsubmit="return confirm('Delete this post?')">
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
                <div class="welcome">
                    <h2>Select a User</h2>
                    <p>Choose an adventurer from the left to inspect their posts and account.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>

</body>
</html>