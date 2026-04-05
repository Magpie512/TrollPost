<aside id="TrendingContainer" class="rightSide">
    <h3>Stories Across Faerûn</h3>
    <?php
    $trendStmt = $pdo->query("SELECT headline, body FROM trending_stories ORDER BY sort_order ASC, id ASC");
    $stories = $trendStmt->fetchAll();
    if (empty($stories)): ?>
        <p style="font-size:11px; font-style:italic; color:#8b6914;">No stories yet.</p>
    <?php else:
        foreach ($stories as $story): ?>
            <article>
                <h4><?= htmlspecialchars($story['headline']) ?></h4>
                <?php if (!empty($story['body'])): ?>
                    <p><?= htmlspecialchars($story['body']) ?></p>
                <?php endif; ?>
            </article>
    <?php endforeach;
    endif; ?>
</aside>