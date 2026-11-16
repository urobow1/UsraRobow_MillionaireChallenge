<?php
/**
 * register.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * This page lets two players create accounts before starting the game.
 * It checks the usernames and passwords, saves the passwords securely,
 * and keeps the registered players in the session for login.php.
 */

session_start();

header('Cache-Control: no-store');

/* ---------- 1. Set up the session ---------- */

// Keep track of the accounts that have been registered.
if (!isset($_SESSION['registered_users']) ||
    !is_array($_SESSION['registered_users'])) {
    $_SESSION['registered_users'] = [];
}

$error = '';
$success = '';

/* ---------- 2. Helper function ---------- */

// Make sure text is safe to show on the page.
function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------- 3. Check the registration form ---------- */

// Check the information when someone submits the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // Make sure all fields are filled out.
    if ($username === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill out all fields.';

    // Check that the username has the correct format.
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $error = 'Username must be 3-20 characters and use only letters, numbers, or underscores.';

    // Make sure the password is long enough.
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';

    // Make sure both passwords match.
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';

    } else {

        $key = strtolower($username);

        // Checks if someone already registered with this username.
        if (isset($_SESSION['registered_users'][$key])) {
            $error = 'This username is already taken.';

        // Only allow two players to register.
        } elseif (count($_SESSION['registered_users']) >= 2) {
            $error = 'Both players are already registered.';

        } else {

            // Save the username and hashed password in the session.
            $_SESSION['registered_users'][$key] = [
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ];

            $success = 'Account created successfully!';

            // Check how many players have registered.
            if (count($_SESSION['registered_users']) === 2) {
                $success = 'Both players are registered! You can log in now.';
            } else {
                $success = 'Player 1 registered! Player 2 can register now.';
            }
        }
    }
}

/* ---------- 4. Show the registration page ---------- */

// Count how many players have registered.
$registeredCount = count($_SESSION['registered_users']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Millionaire Challenge - Register</title>
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
            <h2>Player Registration</h2>

            <p>Create an account to play the game.</p>

            <?php if ($error !== ''): ?>
                <p class="alert" role="alert">
                    <?= h($error) ?>
                </p>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <p role="status">
                    <?= h($success) ?>
                </p>
            <?php endif; ?>

            <?php if ($registeredCount < 2): ?>

                <p>
                    Register Player <?= $registeredCount + 1 ?>
                </p>

                <form class="form-stack" method="post" action="register.php">

                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        minlength="3"
                        maxlength="20"
                        pattern="[A-Za-z0-9_]{3,20}"
                        autocomplete="username"
                        required
                    >

                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button class="pill" type="submit">
                        Register
                    </button>

                </form>

            <?php else: ?>

                <p role="status">
                    Both players have registered successfully!
                </p>

                <a class="pill" href="login.php">
                    Go to Login
                </a>

            <?php endif; ?>

            <div class="confirm-actions">
                <a class="pill" href="login.php">Login</a>
                <a class="pill" href="index.php">Back to Home</a>
            </div>

        </section>
    </main>

</div>
</body>
</html>
