<aside id="AdvertContainer" class="rightSideAlt">
    <?php
    $adStmt = $pdo->query("SELECT title, url, tagline FROM ads WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $ad = $adStmt->fetch();
    if ($ad): ?>
        <a href="<?= htmlspecialchars($ad['url']) ?>" target="_blank" rel="noopener noreferrer">
            <h3><?= htmlspecialchars($ad['title']) ?></h3>
        </a>
        <?php if (!empty($ad['tagline'])): ?>
            <p class="tagline"><?= htmlspecialchars($ad['tagline']) ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p style="font-size:11px; font-style:italic; color:#8b6914;">No active ads.</p>
    <?php endif; ?>
</aside>