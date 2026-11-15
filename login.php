<?php
/**
 * login.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * This page lets registered players log in using their username and
 * password. It checks their information, keeps track of both players
 * in the session, and sends them to the homepage when they are ready.
 */

session_start();

header('Cache-Control: no-store');

/* ---------- 1. Set up the session ---------- */

// Get the registered accounts from the session.
if (!isset($_SESSION['registered_users']) ||
    !is_array($_SESSION['registered_users'])) {
    $_SESSION['registered_users'] = [];
}

// Keep track of which players have logged in.
if (!isset($_SESSION['authenticated_players']) ||
    !is_array($_SESSION['authenticated_players'])) {
    $_SESSION['authenticated_players'] = [];
}

$error = '';
$success = '';

/* ---------- 2. Helper function ---------- */

// Make sure text is safe to show on the page.
function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------- 3. Check the login form ---------- */

// Check the username and password when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';

    } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $error = 'Please enter a valid username.';

    } else {
        $key = strtolower($username);
        $account = $_SESSION['registered_users'][$key] ?? null;

        // Check the password using the saved password hash.
        if (!is_array($account) ||
            !isset($account['password_hash']) ||
            !password_verify($password, $account['password_hash'])) {

            $error = 'Incorrect username or password.';

        } else {
            $savedName = $account['username'];

            // Do not let the same player log in twice.
            $alreadyLoggedIn = false;

            foreach ($_SESSION['authenticated_players'] as $player) {
                if (strcasecmp($player, $savedName) === 0) {
                    $alreadyLoggedIn = true;
                    break;
                }
            }

            if ($alreadyLoggedIn) {
                $error = 'This player is already logged in.';

            } elseif (count($_SESSION['authenticated_players']) >= 2) {
                $error = 'Both players are already logged in.';

            } else {

                // Update the session after a successful login.
                session_regenerate_id(true);

                $_SESSION['authenticated_players'][] = $savedName;

                // Check if both players have logged in.
                if (count($_SESSION['authenticated_players']) === 2) {

                    $_SESSION['players'] =
                        array_values($_SESSION['authenticated_players']);

                    $_SESSION['username'] = $_SESSION['players'][0];

                    // Clear the previous game before starting again.
                    unset($_SESSION['game']);

                    header('Location: index.php');
                    exit;

                } else {
                    $success = 'Player 1 logged in! Player 2 can log in now.';
                }
            }
        }
    }
}

/* ---------- 4. Show the login page ---------- */

$loggedInCount = count($_SESSION['authenticated_players']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Millionaire Challenge - Login</title>
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
        <section class="screen confirm-screen">

            <h1 class="big-title">Millionaire Challenge</h1>
            <h2>Player Login</h2>

            <p>Both players must log in before starting the game.</p>

            <?php if ($error !== ''): ?>
                <p class="alert" role="alert"><?= h($error) ?></p>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <p role="status"><?= h($success) ?></p>
            <?php endif; ?>

            <?php if ($loggedInCount < 2): ?>

                <p>
                    Player <?= $loggedInCount + 1 ?>:
                    Enter your username and password.
                </p>

                <form class="form-stack" method="post" action="login.php">

                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        maxlength="20"
                        autocomplete="username"
                        required
                    >

                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                    <button class="pill" type="submit">
                        Login
                    </button>

                </form>

            <?php else: ?>

                <p role="status">Both players are logged in!</p>

                <a class="pill" href="index.php">
                    Go to Homepage
                </a>

            <?php endif; ?>

            <div class="confirm-actions">
                <a class="pill" href="register.php">Register</a>
                <a class="pill" href="index.php">Back to Home</a>
            </div>

        </section>
    </main>

</div>
</body>
</html>


