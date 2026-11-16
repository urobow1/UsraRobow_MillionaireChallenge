<?php
/**
 * scores.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * Shared score storage used by game.php (saving) and leaderboard.php (reading).
 * Line format in scores.txt:  time|name|score|players
 *
 * Scores are written to data/scores.txt. If that folder can't be created or
 * written (common on shared school servers), a temp-folder copy is used, and
 * every score is also kept in the session so the leaderboard always shows it.
 */

/** Folders to try, in order. */
function score_dirs(): array
{
    return [
        __DIR__ . '/data',
        rtrim(sys_get_temp_dir(), '/\\') . '/millionaire_challenge',
    ];
}

/** Create the folder if needed; true when it can be written to. */
function prepare_dir(string $dir): bool
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }
    $ht = $dir . '/.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht,
            "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    return true;
}

/**
 * Save one finished player. Returns true if it reached a score FILE
 * (false means only the session copy was kept).
 */
function save_score(string $name, int $score, int $players): bool
{
    $line = time() . '|' . str_replace(['|', "\r", "\n"], ' ', $name) . '|' . $score . '|' . $players;

    // Session copy: guarantees the leaderboard shows it for this player
    $_SESSION['scores'][] = $line;

    foreach (score_dirs() as $dir) {
        if (prepare_dir($dir)
            && @file_put_contents($dir . '/scores.txt', $line . "\n", FILE_APPEND | LOCK_EX) !== false) {
            return true;
        }
    }
    return false;
}

/** Read every score source, merge, drop duplicates and bad lines. */
function load_scores(): array
{
    $lines = [];
    foreach (score_dirs() as $dir) {
        $file = $dir . '/scores.txt';
        if (is_readable($file)) {
            $lines = array_merge($lines, file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        }
    }
    if (isset($_SESSION['scores']) && is_array($_SESSION['scores'])) {
        $lines = array_merge($lines, $_SESSION['scores']);
    }

    $rows = [];
    foreach (array_unique($lines) as $line) {
        $parts = explode('|', (string)$line);
        if (count($parts) < 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[2])) {
            continue;
        }
        $rows[] = ['time' => (int)$parts[0], 'name' => $parts[1], 'score' => (int)$parts[2]];
    }
    return $rows;
}