<?php
session_start();
require 'includes/connect.php';
require_once 'includes/header.php';
?>

<body>
    <main class="layout">
        <header class="header">
            <img src="img/gob.png"
                onerror="this.src='../img/gob.png'; this.onerror='this.src=&#34;../../img/gob.png&#34;; this.onerror=null;'"
                class="logo" alt="TrollPost Logo" decoding="async">
            <h1 class="embossed"> TrollPost </h1>

            <input type="text" id="searchBar" placeholder="Scry?" class="form-control">

            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="color:#ffcc44; font-size:11px; white-space:nowrap;">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                </span>
                <button class="fancy-button" onclick="window.location.href='pages/profile.php'">My Profile</button>
                <button class="fancy-button" onclick="window.location.href='includes/logout.php'">Log Out</button>
            <?php else: ?>
                <button id="signin" class="btn btn-primary" onclick="window.location.href='pages/SL.php'">Log in or Sign
                    up</button>
            <?php endif; ?>
        </header>

        <?php include 'includes/herooftheweek.php'; ?>

        <section class="body">
            <div id="CreatePostsContainer">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="process.php" method="post" id="PostForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <label for="PostContent">Your Post:</label>
                        <textarea id="PostContent" name="content" rows="4" cols="50" maxlength="500"
                            placeholder="Write your troll post here..."></textarea>
                        <div style="margin-top:6px;">
                            <label for="post_image" style="font-size:11px;">Attach Image (optional):</label>
                            <input type="file" name="post_image" id="post_image" accept="image/*"
                                style="font-size:11px; margin-top:4px;">
                        </div>
                        <input type="submit" value="Submit Post" style="margin-top:8px;">
                    </form>
                <?php else: ?>
                    <p>You must be <a href="Trollpost/Pages/SL.php">logged in</a> to post.</p>
                <?php endif; ?>
            </div>

            <div id="FeedContainer">
                <h2>Recent Posts</h2>
                <?php
                $limit = 10;
                $stmt = $pdo->prepare("SELECT posts.id, posts.user_id, posts.content, posts.image_path, posts.created_at, users.username 
                        FROM posts 
                        JOIN users ON posts.user_id = users.id 
                        ORDER BY posts.id DESC LIMIT :limit");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                while ($post = $stmt->fetch()):
                    ?>
                    <div class="PostCard" id="post-<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <span class="post-meta">
                                Posted by <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                                · <?php echo $post['created_at']; ?>
                            </span>
                        </div>
                    
                        <div id="display-<?php echo $post['id']; ?>">
                            <p class="content-text"><?php echo htmlspecialchars($post['content']); ?></p>
                            <?php if (!empty($post['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image"
                                    style="max-width:100%; margin-top:6px; border:2px solid #c4a070; display:block;">
                            <?php endif; ?>
                        </div>

                        <div class="post-footer">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                                <button class="btn-interaction" onclick="toggleEdit(<?php echo $post['id']; ?>)">Edit</button>
                                <form action="process.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn-interaction"
                                        onclick="return confirm('Delete this post?')">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <?php include 'includes/trending.php'; ?>
        <?php include 'includes/advert.php'; ?>

        <script src="js/js.js" defer></script>

        <?php require_once 'includes/footer.php'; ?>