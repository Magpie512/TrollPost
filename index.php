<?php
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
            <button id="signin" class="btn btn-primary" onclick="window.location.href='pages/SL.php'"> Sign In </button>
            <button id="signup" class="btn btn-secondary" onclick="window.location.href='pages/SL.php'"> Sign Up </button>
        </header>

        <?php include 'includes/herooftheweek.php'; ?>

        <section class="body">
            <div id="CreatePostsContainer">
                <?php if (isset($_SESSION['user_id'])): // Only show post form if logged in ?>
                    <form action="process.php" method="post" id="PostForm">
                        <input type="hidden" name="action" value="create">
                        <label for="PostContent"> Your Post: </label>
                        <textarea id="PostContent" name="content" rows="4" cols="50" maxlength="500"
                            placeholder="Write your troll post here..."></textarea>
                        <input type="submit" value="Submit Post">
                    </form>
                <?php else: // Show login prompt if not logged in ?>
                    <p>You must be <a href="pages/SL.php">logged in</a> to post.</p>
                <?php endif; ?>
            </div>

            <div id="FeedContainer">
                <h2>Recent Posts</h2>
                <?php
                $limit = 10;
                $stmt = $pdo->prepare("SELECT id, content, created_at FROM posts ORDER BY id DESC LIMIT :limit");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                while ($post = $stmt->fetch()):
                    ?>
                    <div class="PostCard" id="post-<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <span class="post-meta"><?php echo $post['created_at']; ?></span>
                        </div>

                        <div id="display-<?php echo $post['id']; ?>">
                            <p class="content-text"><?php echo htmlspecialchars($post['content']); ?></p>
                        </div>

                        <div class="post-footer">
                            <?php if (isset($_SESSION['user_id'])): // Only show edit/delete if logged in ?>
                                <button class="btn-interaction" onclick="toggleEdit(<?php echo $post['id']; ?>)">Edit</button>
                                <form action="process.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn-interaction">Delete</button>
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