<section id="HOTWContainer" class="leftSide">
    <h2><abbr title="Heroes of the Week">H.O.T.W</abbr></h2>
    <?php
    $heroStmt = $pdo->query("SELECT name, description FROM heroes_of_week ORDER BY sort_order ASC, id ASC LIMIT 3");
    $heroes = $heroStmt->fetchAll();
    if (empty($heroes)): ?>
        <p style="font-size:11px; font-style:italic; color:#8b6914;">No heroes this week.</p>
    <?php else:
        foreach ($heroes as $i => $hero): ?>
            <div id="Hero<?= $i + 1 ?>" class="HeroCard">
                <h3><?= htmlspecialchars($hero['name']) ?></h3>
                <p><?= htmlspecialchars($hero['description']) ?></p>
            </div>
    <?php endforeach;
    endif; ?>
</section>