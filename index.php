<?php
require 'includes/connect.php'; #connects to the database
require_once 'includes/header.php'; 
?>

<body>
    <main class="layout">
        <header class="header"> <!-- I did use AI right here I will admit -->
            <img src="img/gob.png"
                onerror="this.src='../img/gob.png'; this.onerror='this.src=&#34;../../img/gob.png&#34;; this.onerror=null;'"
                class="logo" alt="TrollPost Logo" decoding="async">
            <h1 class="embossed"> TrollPost </h1>


            <input type="text" id="searchBar" placeholder="Scry?" class="form-control">
            <button id="signin" class="btn btn-primary" onclick="window.location.href='pages/SL.php'"> Sign In </button>
            <button id="signup" class="btn btn-secondary" onclick="window.location.href='pages/SL.php'"> Sign Up
            </button>
        </header>

        <!-- Layout for clickable hero cards and that can pop out to showcase the HERO OF THE WEEK -->
        <?php include 'includes/herooftheweek.php'; ?>

        <section class="body">
            <div id="CreatePostsContainer">
                <form action="process.php" method="post" id="PostForm">
                    <input type="hidden" name="action" value="create">
                    <label for="PostContent"> Your Post: </label>
                    <textarea id="PostContent" name="content" rows="4" cols="50" maxlength="500"
                        placeholder="Write your troll post here..."></textarea>
                    <input type="submit" value="Submit Post">
                </form>
            </div>
            <!-- LE ASSIGNMENT -->
            <div id="FeedContainer">
                <h2>Recent Posts</h2>
                <?php
                $limit = 10; // Number of posts to display
                $stmt = $pdo->prepare("SELECT id, content, created_at FROM posts ORDER BY id DESC LIMIT :limit");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                while ($post = $stmt->fetch()):
                    ?>
                    <div class="PostCard" id="post-<?php echo $post['id']; ?>">
                        <!-- Each post card has a unique ID based on the post ID for easy JavaScript manipulation -->
                        <div class="post-header">
                            <!-- Optionally display the post ID as a header -->
                            <span class="post-meta"><?php echo $post['created_at']; ?></span>
                        </div>

                        <div id="display-<?php echo $post['id']; ?>">
                            <p class="content-text"><?php echo htmlspecialchars($post['content']); ?></p>
                        </div>

                        <div class="post-footer">
                            <button class="btn-interaction" onclick="toggleEdit(<?php echo $post['id']; ?>)">Edit</button>

                            <form action="process.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn-interaction">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Joke Container for silly made up stories like "Local man becomes Giant" or "Whizbang's new line of gnome-sized furniture is a hit" -->
        <?php include 'includes/trending.php'; ?>

        <?php include 'includes/advert.php'; ?>

        <script src="js/js.js" defer></script> <!-- MORE EFFICIENT GET PWNED -->

        <?php require_once 'includes/footer.php'; ?>