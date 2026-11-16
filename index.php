<?php
/**
 * index.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * This is the homepage for our Millionaire Challenge game. Players can
 * register or log in here. The page checks if both players are ready
 * before letting them start the game.
 */

session_start();

header('Cache-Control: no-store');

/* ---------- 1. Check the session ---------- */

// Check if someone is logged in.
$loggedIn = !empty($_SESSION['username']);

// Get the players from the session.
$players = [];

if (isset($_SESSION['players']) && is_array($_SESSION['players'])) {
    foreach ($_SESSION['players'] as $player) {
        if (is_array($player)) {
            $name = $player['username'] ?? $player['name'] ?? '';
        } else {
            $name = $player;
        }

        if (is_string($name) && trim($name) !== '') {
            $players[] = trim($name);
        }
    }
}

// Makes sure we have two different players before starting.
$ready = $loggedIn && count($players) === 2
    && strcasecmp($players[0], $players[1]) !== 0
    && strcasecmp($players[0], (string)$_SESSION['username']) === 0;

/* ---------- 2. Helper function ---------- */

// Makes sure usernames are safe to show on the page.
function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------- 3. Start the game ---------- */

// If both players are ready, send them to the game page.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_game'])) {
    if ($ready) {
        header('Location: game.php?new=1');
        exit;
    }
}

/* ---------- 4. Show the homepage ---------- */

// Get the username so we can show it on the page.
$username = $loggedIn ? (string)$_SESSION['username'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Millionaire Challenge - Home</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="stage">

    <div class="deco" aria-hidden="true">
        <div class="side side-left">
            <span class="qm">?</span>
            <span class="buzzer"></span>
        </div>
        <div class="side side-right">
            <span class="qm">?</span>
            <span class="buzzer"></span>
        </div>
        <div class="floor"></div>
    </div>

    <main class="marquee">
        <section class="screen confirm-screen home-screen">

            <h1 class="big-title">Millionaire Challenge</h1>

            <p>Who Wants to Be a Millionaire?</p>

            <?php if (!$loggedIn): ?>

                <p>Welcome to Millionaire Challenge!</p>
                <p>Register or log in to start playing.</p>

                <div class="confirm-actions">
                    <a class="pill" href="register.php">Register</a>
                    <a class="pill" href="login.php">Login</a>
                </div>

            <?php elseif (!$ready): ?>

                <p role="status">
                    Welcome, <?= h($username) ?>!
                </p>

                <p>Two players are required to start the game.</p>

                <?php if (!empty($players)): ?>
                    <p>
                        Players ready:
                        <?= h(implode(' and ', $players)) ?>
                    </p>
                <?php endif; ?>

                <div class="confirm-actions">
                    <a class="pill" href="register.php">Register Player</a>
                    <a class="pill" href="login.php">Login Player</a>
                </div>

            <?php else: ?>

                <p role="status">Both players are ready!</p>

                <p>Player 1: <?= h($players[0]) ?></p>
                <p>Player 2: <?= h($players[1]) ?></p>

                <p>
                    Answer 15 questions and compete for $1,000,000!
                </p>

                <form method="post" action="index.php">
                    <button class="pill" type="submit"
                            name="start_game" value="1">
                        Start Game
                    </button>
                </form>

            <?php endif; ?>

        </section>
    </main>

    <?php if ($loggedIn): ?>
        <nav class="corner-bar" aria-label="Account navigation">
            <a class="pill" href="logout.php?from=game">Logout</a>
        </nav>
    <?php endif; ?>

</div>
</body>
</html>
