<?php
/**
 * leaderboard.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * Reads data/scores.txt (written by game.php; one line per finished player:
 *   timestamp|name|score|players), sorts the rows with usort() and shows the
 * top 10.  Requires a logged-in session.
 */

session_start();

// Session guard
if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

header('Cache-Control: no-store');

const TOP_N    = 10;   // rows shown at most
const MIN_ROWS = 4;    // empty rows pad the grid so it matches the wireframe

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/scores.php';   // shared load_scores()

$rows = load_scores();

// Highest score first; earlier result wins a tie
usort($rows, function ($a, $b) {
    return [$b['score'], $a['time']] <=> [$a['score'], $b['time']];
});
$rows = array_slice($rows, 0, TOP_N);

// Highlight the logged-in player's own rows
$me = mb_strtolower(trim((string)$_SESSION['username']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Millionaire Challenge - Leaderboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="stage">
    <div class="deco" aria-hidden="true">
        <div class="side side-left"><span class="qm">?</span><span class="buzzer"></span></div>
        <div class="side side-right"><span class="qm">?</span><span class="buzzer"></span></div>
        <div class="floor"></div>
    </div>

    <main class="marquee">
        <section class="screen">
            <h1 class="title-pill">Leaderboard</h1>

            <div class="board-wrap">
                <?php if (!$rows): ?>
                    <p class="board-empty">No scores yet &mdash; finish a game to get on the board!</p>
                <?php else: ?>
                    <table class="board">
                        <caption class="sr-only">Top <?= TOP_N ?> scores</caption>
                        <thead class="sr-only"><tr><th scope="col">Player</th><th scope="col">Winnings</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $i => $r): ?>
                            <tr class="<?= $i === 0 ? 'top1 ' : '' ?><?= ($me !== '' && mb_strtolower($r['name']) === $me) ? 'me' : '' ?>">
                                <td><span class="rank"><?= $i + 1 ?>.</span> <?= h($r['name']) ?></td>
                                <td>$<?= number_format($r['score']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php for ($i = count($rows); $i < MIN_ROWS; $i++): ?>
                            <tr class="blank" aria-hidden="true"><td></td><td></td></tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <nav class="corner-bar">
        <a class="pill" href="logout.php?from=leaderboard">Logout</a>
        <a class="pill" href="game.php?new=1">Play Again</a>
    </nav>
</div>
</body>
</html>
