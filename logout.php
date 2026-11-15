<?php
/**
 * logout.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * Shows the "Are you sure?" confirmation from the wireframe.
 *   Yes -> POST: destroy the session, clear the session cookie, redirect to index.php
 *   No  -> go back to the page the player came from (game.php or leaderboard.php)
 *
 * Logging out uses a POST form + token so a stray link or prefetch can't end a session.
 */

session_start();

// Not logged in? Nothing to log out of.
if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$allowed = ['game' => 'game.php', 'leaderboard' => 'leaderboard.php'];

// Where "No" returns to (whitelisted so it can't be used as an open redirect)
$from = $_POST['from'] ?? $_GET['from'] ?? 'game';
$from = isset($allowed[$from]) ? $from : 'game';

if (empty($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['confirm'], $_POST['token'])
    && $_POST['confirm'] === 'yes'
    && hash_equals($_SESSION['logout_token'], (string)$_POST['token'])) {

    // Clear every session variable, expire the cookie, destroy the session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
    }
    session_destroy();

    header('Location: index.php');
    exit;
}

header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Millionaire Challenge - Logout</title>
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
        <section class="screen confirm-screen">
            <h1 class="big-title">Logout</h1>
            <p class="pill ask">Are you sure?</p>

            <div class="confirm-actions">
                <form method="post" action="logout.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['logout_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="pill" type="submit" name="confirm" value="yes">Yes</button>
                </form>
                <a class="pill" href="<?= htmlspecialchars($allowed[$from], ENT_QUOTES, 'UTF-8') ?>">No</a>
            </div>
        </section>
    </main>
</div>
</body>
</html>
