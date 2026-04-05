<?php
session_start();
require '../includes/connect.php';
require '../includes/sanitize.php';

// Must be logged in AND be an admin
if ($_SESSION['isadmin'] != 1) {
    header("Location: ../index.php");
    exit;
}

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── User / Post management ────────────────────────────────
    if ($action === 'delete_post') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            if ($post && !empty($post['image_path']) && file_exists('../' . $post['image_path'])) {
                unlink('../' . $post['image_path']);
            }
            $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);
            $success = "Post deleted.";
        }
    }

    if ($action === 'delete_user') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId > 0 && $targetId !== (int) $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $success = "User and all their posts deleted.";
        } else {
            $error = "You cannot delete your own admin account.";
        }
    }

    // ── Heroes of the Week ────────────────────────────────────
    if ($action === 'add_hero') {
        $name  = sanitizeText($_POST['hero_name'] ?? '', 100);
        $desc  = sanitizeText($_POST['hero_desc'] ?? '', 500);
        $order = (int) ($_POST['hero_order'] ?? 0);
        if ($name !== '') {
            $pdo->prepare("INSERT INTO heroes_of_week (name, description, sort_order) VALUES (?, ?, ?)")
                ->execute([$name, $desc, $order]);
            $success = "Hero added.";
        } else {
            $error = "Hero name cannot be empty.";
        }
    }

    if ($action === 'edit_hero') {
        $id    = (int) ($_POST['hero_id'] ?? 0);
        $name  = sanitizeText($_POST['hero_name'] ?? '', 100);
        $desc  = sanitizeText($_POST['hero_desc'] ?? '', 500);
        $order = (int) ($_POST['hero_order'] ?? 0);
        if ($id > 0 && $name !== '') {
            $pdo->prepare("UPDATE heroes_of_week SET name = ?, description = ?, sort_order = ? WHERE id = ?")
                ->execute([$name, $desc, $order, $id]);
            $success = "Hero updated.";
        }
    }

    if ($action === 'delete_hero') {
        $id = (int) ($_POST['hero_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM heroes_of_week WHERE id = ?")->execute([$id]);
            $success = "Hero removed.";
        }
    }

    // ── Ads ───────────────────────────────────────────────────
    if ($action === 'add_ad') {
        $title   = sanitizeText($_POST['ad_title'] ?? '', 150);
        $url     = sanitizeText($_POST['ad_url'] ?? '', 500);
        $tagline = sanitizeText($_POST['ad_tagline'] ?? '', 500);
        $active  = isset($_POST['ad_active']) ? 1 : 0;
        if ($title !== '' && $url !== '') {
            $pdo->prepare("INSERT INTO ads (title, url, tagline, is_active) VALUES (?, ?, ?, ?)")
                ->execute([$title, $url, $tagline, $active]);
            $success = "Ad added.";
        } else {
            $error = "Ad title and URL are required.";
        }
    }

    if ($action === 'edit_ad') {
        $id      = (int) ($_POST['ad_id'] ?? 0);
        $title   = sanitizeText($_POST['ad_title'] ?? '', 150);
        $url     = sanitizeText($_POST['ad_url'] ?? '', 500);
        $tagline = sanitizeText($_POST['ad_tagline'] ?? '', 500);
        $active  = isset($_POST['ad_active']) ? 1 : 0;
        if ($id > 0 && $title !== '' && $url !== '') {
            $pdo->prepare("UPDATE ads SET title = ?, url = ?, tagline = ?, is_active = ? WHERE id = ?")
                ->execute([$title, $url, $tagline, $active, $id]);
            $success = "Ad updated.";
        }
    }

    if ($action === 'delete_ad') {
        $id = (int) ($_POST['ad_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
            $success = "Ad deleted.";
        }
    }

    // ── Trending ──────────────────────────────────────────────
    if ($action === 'add_story') {
        $headline = sanitizeText($_POST['story_headline'] ?? '', 255);
        $body     = sanitizeText($_POST['story_body'] ?? '', 1000);
        $order    = (int) ($_POST['story_order'] ?? 0);
        if ($headline !== '') {
            $pdo->prepare("INSERT INTO trending_stories (headline, body, sort_order) VALUES (?, ?, ?)")
                ->execute([$headline, $body, $order]);
            $success = "Story added.";
        } else {
            $error = "Headline cannot be empty.";
        }
    }

    if ($action === 'edit_story') {
        $id       = (int) ($_POST['story_id'] ?? 0);
        $headline = sanitizeText($_POST['story_headline'] ?? '', 255);
        $body     = sanitizeText($_POST['story_body'] ?? '', 1000);
        $order    = (int) ($_POST['story_order'] ?? 0);
        if ($id > 0 && $headline !== '') {
            $pdo->prepare("UPDATE trending_stories SET headline = ?, body = ?, sort_order = ? WHERE id = ?")
                ->execute([$headline, $body, $order, $id]);
            $success = "Story updated.";
        }
    }

    if ($action === 'delete_story') {
        $id = (int) ($_POST['story_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM trending_stories WHERE id = ?")->execute([$id]);
            $success = "Story deleted.";
        }
    }

    $qs  = isset($_POST['search']) ? '?search=' . urlencode($_POST['search']) : '';
    $tab = isset($_POST['tab'])    ? (($qs ? '&' : '?') . 'tab=' . urlencode($_POST['tab'])) : '';
    header("Location: admin.php" . $qs . $tab);
    exit;
}

// ── Load data ─────────────────────────────────────────────────
$search     = sanitizeSearch($_GET['search'] ?? '');
$viewUserId = isset($_GET['user']) ? (int) $_GET['user'] : null;
$activeTab  = $_GET['tab'] ?? 'users';

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at, isadmin FROM users
                           WHERE username LIKE ? OR email LIKE ?
                           ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT id, username, email, created_at, isadmin FROM users ORDER BY created_at DESC");
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
$heroes     = $pdo->query("SELECT * FROM heroes_of_week ORDER BY sort_order ASC, id ASC")->fetchAll();
$ads        = $pdo->query("SELECT * FROM ads ORDER BY id DESC")->fetchAll();
$stories    = $pdo->query("SELECT * FROM trending_stories ORDER BY sort_order ASC, id ASC")->fetchAll();
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
            --bg: #8a744e; --panel: #3a2c18; --panel2: #46351c;
            --border: #8a5e20; --gold: #e2b45a; --gold-lt: #f6d47a;
            --gold-dim: #b08432; --red: #b83a3a; --red-lt: #e05a5a;
            --text: #f6edd8; --text-dim: #c8b890;
            --green: #4c8f4c; --green-lt: #78c878; --input-bg: #2a1e08;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: 'Crimson Text', Georgia, serif; font-size: 15px; min-height: 100vh; }

        .topbar { background: linear-gradient(to bottom, #2a1e08, #0d0a06); border-bottom: 2px solid var(--gold-dim); padding: 12px 24px; display: flex; align-items: center; gap: 16px; position: sticky; top: 0; z-index: 100; }
        .topbar img { height: 36px; image-rendering: pixelated; }
        .topbar h1 { font-family: 'Cinzel', serif; font-size: 1.1rem; color: var(--gold-lt); letter-spacing: 3px; text-transform: uppercase; flex: 1; }
        .topbar-links { display: flex; gap: 10px; }
        .topbar-links a { color: var(--text-dim); text-decoration: none; font-size: 12px; font-family: 'Cinzel', serif; letter-spacing: 1px; text-transform: uppercase; padding: 4px 10px; border: 1px solid var(--border); transition: all 0.2s; }
        .topbar-links a:hover { color: var(--gold-lt); border-color: var(--gold-dim); }

        .wrap { display: grid; grid-template-columns: 280px 1fr; min-height: calc(100vh - 62px); }
        .sidebar { background: var(--panel); border-right: 2px solid var(--border); display: flex; flex-direction: column; }
        .main { background: var(--bg); padding: 24px; overflow-y: auto; }

        .stats-row { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid var(--border); }
        .stat-box { padding: 16px 12px; text-align: center; border-right: 1px solid var(--border); }
        .stat-box:last-child { border-right: none; }
        .stat-num { font-family: 'Cinzel', serif; font-size: 1.8rem; color: var(--gold-lt); line-height: 1; }
        .stat-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }

        .tab-nav { display: flex; flex-direction: column; border-bottom: 1px solid var(--border); }
        .tab-btn { background: none; border: none; border-bottom: 1px solid var(--border); color: var(--text-dim); font-family: 'Cinzel', serif; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; padding: 10px 14px; text-align: left; cursor: pointer; transition: background 0.15s; border-radius: 0; }
        .tab-btn:hover { background: var(--panel2); color: var(--gold-lt); }
        .tab-btn.active { background: var(--panel2); color: var(--gold-lt); border-left: 3px solid var(--gold); }

        .search-wrap { padding: 14px 12px; border-bottom: 1px solid var(--border); }
        .search-wrap form { display: flex; gap: 6px; }
        .search-wrap input { flex: 1; background: var(--bg); border: 1px solid var(--border); color: var(--text); font-family: 'Crimson Text', serif; font-size: 14px; padding: 6px 10px; outline: none; }
        .search-wrap input:focus { border-color: var(--gold-dim); }
        .search-wrap input::placeholder { color: var(--text-dim); font-style: italic; }
        .btn-search { background: linear-gradient(to bottom, var(--gold), var(--gold-dim)); border: none; color: #1a0e00; font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; padding: 6px 12px; cursor: pointer; text-transform: uppercase; }
        .btn-search:hover { opacity: 0.85; }
        .sidebar-title { padding: 10px 12px 6px; font-family: 'Cinzel', serif; font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; }

        .user-list { overflow-y: auto; flex: 1; }
        .user-row { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-bottom: 1px solid var(--border); text-decoration: none; color: var(--text); transition: background 0.15s; }
        .user-row:hover { background: var(--panel2); }
        .user-row.active { background: var(--panel2); border-left: 3px solid var(--gold); }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-dim), var(--border)); display: flex; align-items: center; justify-content: center; font-family: 'Cinzel', serif; font-size: 13px; color: var(--gold-lt); flex-shrink: 0; border: 1px solid var(--border); }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-family: 'Cinzel', serif; font-size: 12px; color: var(--gold-lt); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: 11px; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge-admin { font-size: 9px; font-family: 'Cinzel', serif; background: var(--gold-dim); color: #1a0e00; padding: 1px 5px; text-transform: uppercase; letter-spacing: 1px; flex-shrink: 0; }

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
        .btn-delete-user { margin-left: auto; background: linear-gradient(to bottom, var(--red-lt), var(--red)); border: 1px solid #6b1010; color: #fff; font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; padding: 8px 16px; cursor: pointer; text-transform: uppercase; flex-shrink: 0; }
        .btn-delete-user:hover { opacity: 0.85; }

        .section-title { font-family: 'Cinzel', serif; font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; }
        .post-card { background: var(--panel); border: 1px solid var(--border); border-left: 3px solid var(--gold-dim); padding: 12px 14px; margin-bottom: 10px; display: flex; gap: 12px; align-items: flex-start; }
        .post-body { flex: 1; min-width: 0; }
        .post-content { font-size: 14px; color: var(--text); line-height: 1.5; word-break: break-word; margin-bottom: 6px; }
        .post-img { max-width: 120px; max-height: 80px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; }
        .post-meta-row { font-size: 11px; color: var(--text-dim); font-family: 'Cinzel', serif; }
        .btn-delete-post { background: none; border: 1px solid var(--red); color: var(--red-lt); font-family: 'Cinzel', serif; font-size: 10px; padding: 4px 10px; cursor: pointer; text-transform: uppercase; flex-shrink: 0; align-self: center; transition: all 0.2s; }
        .btn-delete-post:hover { background: var(--red); color: #fff; }
        .no-posts { color: var(--text-dim); font-style: italic; font-size: 13px; padding: 20px 0; text-align: center; }

        /* CMS */
        .cms-section h2 { font-family: 'Cinzel', serif; font-size: 1rem; color: var(--gold-lt); border-bottom: 1px solid var(--border); padding-bottom: 8px; margin-bottom: 16px; letter-spacing: 2px; text-transform: uppercase; }
        .cms-card { background: var(--panel); border: 1px solid var(--border); border-left: 3px solid var(--gold-dim); padding: 12px 14px; margin-bottom: 10px; display: flex; align-items: flex-start; gap: 12px; }
        .cms-card-body { flex: 1; min-width: 0; }
        .cms-card-title { font-family: 'Cinzel', serif; font-size: 13px; color: var(--gold-lt); margin-bottom: 3px; }
        .cms-card-desc { font-size: 12px; color: var(--text-dim); line-height: 1.4; word-break: break-word; }
        .cms-card-meta { font-size: 10px; color: var(--text-dim); margin-top: 4px; font-family: 'Cinzel', serif; }
        .cms-actions { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; }

        .cms-form { background: var(--panel); border: 1px solid var(--border); padding: 14px; margin-bottom: 18px; }
        .cms-form h3 { font-family: 'Cinzel', serif; font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .cms-form label { display: block; font-size: 11px; color: var(--text-dim); margin: 8px 0 3px; font-family: 'Cinzel', serif; text-transform: uppercase; letter-spacing: 0.5px; }
        .cms-form input[type="text"],
        .cms-form input[type="url"],
        .cms-form input[type="number"],
        .cms-form textarea { width: 100%; background: var(--input-bg); border: 1px solid var(--border); color: var(--text); font-family: 'Crimson Text', serif; font-size: 13px; padding: 6px 8px; outline: none; resize: vertical; }
        .cms-form input:focus, .cms-form textarea:focus { border-color: var(--gold-dim); }
        .cms-form textarea { min-height: 60px; }
        .cms-form-row { display: flex; gap: 8px; margin-top: 10px; align-items: center; }
        .cms-form input[type="checkbox"] { width: auto; }

        .btn-cms-add { background: linear-gradient(to bottom, var(--gold), var(--gold-dim)); border: none; color: #1a0e00; font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; padding: 6px 14px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }
        .btn-cms-add:hover { opacity: 0.85; }
        .btn-cms-save { background: linear-gradient(to bottom, #4a7a4a, #2a4a2a); border: none; color: #cfc; font-family: 'Cinzel', serif; font-size: 10px; font-weight: 700; padding: 4px 10px; cursor: pointer; text-transform: uppercase; }
        .btn-cms-save:hover { opacity: 0.85; }
        .btn-cms-del { background: none; border: 1px solid var(--red); color: var(--red-lt); font-family: 'Cinzel', serif; font-size: 10px; padding: 3px 8px; cursor: pointer; text-transform: uppercase; transition: all 0.2s; white-space: nowrap; }
        .btn-cms-del:hover { background: var(--red); color: #fff; }
        .badge-inactive { font-size: 9px; font-family: 'Cinzel', serif; background: #555; color: #ccc; padding: 1px 5px; text-transform: uppercase; margin-left: 6px; }
        .badge-active { font-size: 9px; font-family: 'Cinzel', serif; background: var(--green); color: #fff; padding: 1px 5px; text-transform: uppercase; margin-left: 6px; }
        .inline-edit { margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 4px; }

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
        <a href="../index.php">← Back to Site</a>
        <a href="logout.php">Log Out</a>
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

        <div class="tab-nav">
            <button class="tab-btn <?= $activeTab === 'users'    ? 'active' : '' ?>" onclick="location.href='admin.php?tab=users'">👥 Users</button>
            <button class="tab-btn <?= $activeTab === 'heroes'   ? 'active' : '' ?>" onclick="location.href='admin.php?tab=heroes'">🏆 Heroes of the Week</button>
            <button class="tab-btn <?= $activeTab === 'ads'      ? 'active' : '' ?>" onclick="location.href='admin.php?tab=ads'">📢 Advertisements</button>
            <button class="tab-btn <?= $activeTab === 'trending' ? 'active' : '' ?>" onclick="location.href='admin.php?tab=trending'">📜 Trending Stories</button>
        </div>

        <?php if ($activeTab === 'users'): ?>
            <div class="search-wrap">
                <form method="GET" action="admin.php">
                    <input type="hidden" name="tab" value="users">
                    <input type="text" name="search" placeholder="Search username or email…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">Scry</button>
                </form>
                <?php if ($search !== ''): ?>
                    <div style="margin-top:6px; font-size:11px; color:var(--text-dim);">
                        <?= count($users) ?> result(s) for "<strong style="color:var(--gold-dim)"><?= htmlspecialchars($search) ?></strong>"
                        — <a href="admin.php?tab=users" style="color:var(--text-dim);">clear</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sidebar-title"><?= $search !== '' ? 'Search Results' : 'All Adventurers' ?></div>
            <div class="user-list">
                <?php if (empty($users)): ?>
                    <div style="padding:16px 12px; color:var(--text-dim); font-style:italic; font-size:13px;">No users found.</div>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $isActive = ($viewUserId === (int) $u['id']);
                        $initial  = strtoupper(substr($u['username'], 0, 1));
                        $href     = 'admin.php?tab=users&user=' . $u['id'] . ($search !== '' ? '&search=' . urlencode($search) : '');
                        ?>
                        <a href="<?= $href ?>" class="user-row <?= $isActive ? 'active' : '' ?>">
                            <div class="user-avatar"><?= $initial ?></div>
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                            <?php if ($u['isadmin'] == 1): ?><span class="badge-admin">Admin</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </aside>

    <main class="main">

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if ($activeTab === 'users'): ?>
        <!-- USERS -->
            <?php if ($viewedUser): ?>
                <div class="user-detail-header">
                    <div class="detail-avatar"><?= strtoupper(substr($viewedUser['username'], 0, 1)) ?></div>
                    <div class="detail-info">
                        <h2><?= htmlspecialchars($viewedUser['username']) ?></h2>
                        <p><?= htmlspecialchars($viewedUser['email']) ?></p>
                        <p class="detail-meta"><?= count($viewedPosts) ?> post(s) &nbsp;·&nbsp; ID #<?= $viewedUser['id'] ?></p>
                    </div>
                    <?php if ($viewedUser['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="admin.php" onsubmit="return confirm('Delete <?= htmlspecialchars($viewedUser['username']) ?> and ALL their posts?')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $viewedUser['id'] ?>">
                            <input type="hidden" name="tab" value="users">
                            <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
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
                            <form method="POST" action="admin.php" onsubmit="return confirm('Delete this post?')">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="tab" value="users">
                                <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
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

        <?php elseif ($activeTab === 'heroes'): ?>
        <!-- HEROES -->
            <div class="cms-section">
                <h2>🏆 Heroes of the Week</h2>
                <div class="cms-form">
                    <h3>Add New Hero</h3>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_hero">
                        <input type="hidden" name="tab" value="heroes">
                        <label>Name</label>
                        <input type="text" name="hero_name" placeholder="Hero name" required>
                        <label>Description</label>
                        <textarea name="hero_desc" placeholder="What makes them heroic?"></textarea>
                        <label>Sort Order</label>
                        <input type="number" name="hero_order" value="0" min="0" max="99" style="width:80px;">
                        <div class="cms-form-row"><button type="submit" class="btn-cms-add">+ Add Hero</button></div>
                    </form>
                </div>
                <?php if (empty($heroes)): ?>
                    <p style="color:var(--text-dim); font-style:italic; font-size:13px;">No heroes yet.</p>
                <?php else: ?>
                    <?php foreach ($heroes as $h): ?>
                        <div class="cms-card">
                            <div class="cms-card-body">
                                <div class="cms-card-title"><?= htmlspecialchars($h['name']) ?></div>
                                <div class="cms-card-desc"><?= htmlspecialchars($h['description']) ?></div>
                                <div class="cms-card-meta">Order: <?= $h['sort_order'] ?></div>
                                <div class="inline-edit">
                                    <form method="POST" action="admin.php">
                                        <input type="hidden" name="action" value="edit_hero">
                                        <input type="hidden" name="tab" value="heroes">
                                        <input type="hidden" name="hero_id" value="<?= $h['id'] ?>">
                                        <input type="text" name="hero_name" value="<?= htmlspecialchars($h['name']) ?>" required style="margin-bottom:4px;">
                                        <textarea name="hero_desc" style="margin-bottom:4px;"><?= htmlspecialchars($h['description']) ?></textarea>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <input type="number" name="hero_order" value="<?= $h['sort_order'] ?>" min="0" max="99" style="width:70px;">
                                            <button type="submit" class="btn-cms-save">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="cms-actions">
                                <form method="POST" action="admin.php" onsubmit="return confirm('Remove this hero?')">
                                    <input type="hidden" name="action" value="delete_hero">
                                    <input type="hidden" name="tab" value="heroes">
                                    <input type="hidden" name="hero_id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn-cms-del">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($activeTab === 'ads'): ?>
        <!-- ADS -->
            <div class="cms-section">
                <h2>📢 Advertisements</h2>
                <div class="cms-form">
                    <h3>Add New Ad</h3>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_ad">
                        <input type="hidden" name="tab" value="ads">
                        <label>Title</label>
                        <input type="text" name="ad_title" placeholder="e.g. DEERSTALKER PRODUCTIONS" required>
                        <label>URL</label>
                        <input type="text" name="ad_url" placeholder="https://..." required>
                        <label>Tagline</label>
                        <textarea name="ad_tagline" placeholder="Short description shown under the title"></textarea>
                        <div class="cms-form-row">
                            <label style="margin:0; display:flex; align-items:center; gap:4px;">
                                <input type="checkbox" name="ad_active" checked> Active
                            </label>
                            <button type="submit" class="btn-cms-add">+ Add Ad</button>
                        </div>
                    </form>
                </div>
                <?php if (empty($ads)): ?>
                    <p style="color:var(--text-dim); font-style:italic; font-size:13px;">No ads yet.</p>
                <?php else: ?>
                    <?php foreach ($ads as $ad): ?>
                        <div class="cms-card">
                            <div class="cms-card-body">
                                <div class="cms-card-title">
                                    <?= htmlspecialchars($ad['title']) ?>
                                    <span class="<?= $ad['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $ad['is_active'] ? 'Active' : 'Inactive' ?></span>
                                </div>
                                <div class="cms-card-desc"><a href="<?= htmlspecialchars($ad['url']) ?>" target="_blank" style="color:var(--gold-dim); font-size:11px;"><?= htmlspecialchars($ad['url']) ?></a></div>
                                <?php if ($ad['tagline']): ?><div class="cms-card-desc" style="margin-top:3px;"><?= htmlspecialchars($ad['tagline']) ?></div><?php endif; ?>
                                <div class="inline-edit">
                                    <form method="POST" action="admin.php">
                                        <input type="hidden" name="action" value="edit_ad">
                                        <input type="hidden" name="tab" value="ads">
                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                        <input type="text" name="ad_title" value="<?= htmlspecialchars($ad['title']) ?>" required style="margin-bottom:4px;">
                                        <input type="text" name="ad_url" value="<?= htmlspecialchars($ad['url']) ?>" required style="margin-bottom:4px;">
                                        <textarea name="ad_tagline" style="margin-bottom:4px;"><?= htmlspecialchars($ad['tagline']) ?></textarea>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <label style="margin:0; font-size:11px; display:flex; align-items:center; gap:4px;">
                                                <input type="checkbox" name="ad_active" <?= $ad['is_active'] ? 'checked' : '' ?>> Active
                                            </label>
                                            <button type="submit" class="btn-cms-save">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="cms-actions">
                                <form method="POST" action="admin.php" onsubmit="return confirm('Delete this ad?')">
                                    <input type="hidden" name="action" value="delete_ad">
                                    <input type="hidden" name="tab" value="ads">
                                    <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                    <button type="submit" class="btn-cms-del">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($activeTab === 'trending'): ?>
        <!-- TRENDING -->
            <div class="cms-section">
                <h2>📜 Trending Stories</h2>
                <div class="cms-form">
                    <h3>Add New Story</h3>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_story">
                        <input type="hidden" name="tab" value="trending">
                        <label>Headline</label>
                        <input type="text" name="story_headline" placeholder="Story headline" required>
                        <label>Body <span style="font-weight:normal; text-transform:none; letter-spacing:0;">(optional)</span></label>
                        <textarea name="story_body" placeholder="Optional body text"></textarea>
                        <label>Sort Order</label>
                        <input type="number" name="story_order" value="0" min="0" max="99" style="width:80px;">
                        <div class="cms-form-row"><button type="submit" class="btn-cms-add">+ Add Story</button></div>
                    </form>
                </div>
                <?php if (empty($stories)): ?>
                    <p style="color:var(--text-dim); font-style:italic; font-size:13px;">No stories yet.</p>
                <?php else: ?>
                    <?php foreach ($stories as $s): ?>
                        <div class="cms-card">
                            <div class="cms-card-body">
                                <div class="cms-card-title"><?= htmlspecialchars($s['headline']) ?></div>
                                <?php if ($s['body']): ?><div class="cms-card-desc"><?= htmlspecialchars($s['body']) ?></div><?php endif; ?>
                                <div class="cms-card-meta">Order: <?= $s['sort_order'] ?></div>
                                <div class="inline-edit">
                                    <form method="POST" action="admin.php">
                                        <input type="hidden" name="action" value="edit_story">
                                        <input type="hidden" name="tab" value="trending">
                                        <input type="hidden" name="story_id" value="<?= $s['id'] ?>">
                                        <input type="text" name="story_headline" value="<?= htmlspecialchars($s['headline']) ?>" required style="margin-bottom:4px;">
                                        <textarea name="story_body" style="margin-bottom:4px;"><?= htmlspecialchars($s['body']) ?></textarea>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <input type="number" name="story_order" value="<?= $s['sort_order'] ?>" min="0" max="99" style="width:70px;">
                                            <button type="submit" class="btn-cms-save">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="cms-actions">
                                <form method="POST" action="admin.php" onsubmit="return confirm('Delete this story?')">
                                    <input type="hidden" name="action" value="delete_story">
                                    <input type="hidden" name="tab" value="trending">
                                    <input type="hidden" name="story_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn-cms-del">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </main>
</div>

</body>
</html>