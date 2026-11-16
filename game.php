<?php
/**
 * game.php - Millionaire Challenge (CSC 4370 Project 2)
 *
 * Core game logic. One file handles every game screen:
 *   - "question" screen : question, 4 answers, LIFELINE and 50/50 buttons
 *   - "ladder"   screen : money ladder, result of the last answer, NEXT QUESTION
 *
 * Features: session guard, POST form processing (Post/Redirect/Get),
 * "Smart Shuffle" (random question per difficulty level, never repeated in the
 * same game), turn-based play for 1-2 players, safe havens, score saving to
 * data/scores.txt (read by leaderboard.php).  No JavaScript, no database.
 *
 * Session variables used
 *   $_SESSION['username']              set by login.php (logged-in user; guard below)
 *   $_SESSION['players']               set by register.php: two different user registrations 
 *   $_SESSION['score'] / ['round']     kept in sync here for the player whose turn it is
 *   $_SESSION['game']                  full game state (players, turn, current question...)
 *
 */

session_start();

/* ---------- 1. Session guard (Edge Case 2) ---------- */
if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

header('Cache-Control: no-store');

/* ---------- 2. Constants & small helpers ---------- */
const PRIZES      = [100, 200, 300, 500, 1000, 2000, 4000, 8000, 16000, 32000,
                     64000, 125000, 250000, 500000, 1000000];
const SAFE_LEVELS = [5, 10, 15];          // question numbers that are safe havens
const MAX_PLAYERS = 2;
const LETTERS     = ['A', 'B', 'C', 'D'];

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function money(int $n): string
{
    return '$' . number_format($n);
}

function go(string $url = 'game.php'): void
{
    header('Location: ' . $url);
    exit;
}

/** Money a player keeps after a wrong answer: last safe haven passed. */
function safe_prize(int $correctAnswers): int
{
    $keep = 0;
    foreach (SAFE_LEVELS as $lvl) {
        if ($correctAnswers >= $lvl) {
            $keep = PRIZES[$lvl - 1];
        }
    }
    return $keep;
}

function clean_name($name, string $fallback): string
{
    $name = trim(preg_replace('/[\x00-\x1F\x7F|]+/u', ' ', (string)$name));
    $name = mb_substr($name, 0, 20);
    return $name !== '' ? $name : $fallback;
}

require __DIR__ . '/scores.php';   // save_score() / load_scores()

/* ---------- 3. Question bank (15 levels x 3 questions) ----------
   Each entry: [question, CORRECT answer, wrong, wrong, wrong].
   Answer positions are shuffled every time a question is drawn. */
function question_bank(): array
{
    return [
        // Level 1 - $100
        [
            ['Which country is home to the pyramids of Giza?', 'Egypt', 'Greece', 'Mexico', 'Italy'],
            ['How many days are in a week?', '7', '5', '6', '8'],
            ['What color do you get by mixing blue and yellow?', 'Green', 'Purple', 'Orange', 'Brown'],
        ],
        // Level 2 - $200
        [
            ['Which planet is known as the Red Planet?', 'Mars', 'Venus', 'Jupiter', 'Mercury'],
            ['What is the largest ocean on Earth?', 'Pacific Ocean', 'Atlantic Ocean', 'Indian Ocean', 'Arctic Ocean'],
            ['How many continents are there?', '7', '5', '6', '8'],
        ],
        // Level 3 - $300
        [
            ['What is the capital of France?', 'Paris', 'Lyon', 'Marseille', 'Nice'],
            ['Which animal is known as the "King of the Jungle"?', 'Lion', 'Tiger', 'Elephant', 'Gorilla'],
            ['Which gas do plants absorb from the air for photosynthesis?', 'Carbon dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'],
        ],
        // Level 4 - $500
        [
            ['Who painted the Mona Lisa?', 'Leonardo da Vinci', 'Michelangelo', 'Raphael', 'Vincent van Gogh'],
            ['What is the chemical formula for water?', 'H2O', 'CO2', 'O2', 'HO'],
            ['In which sport would you perform a slam dunk?', 'Basketball', 'Volleyball', 'Tennis', 'Baseball'],
        ],
        // Level 5 - $1,000 (safe haven)
        [
            ['What is the hardest natural substance on Earth?', 'Diamond', 'Gold', 'Quartz', 'Iron'],
            ['Which language has the most native speakers worldwide?', 'Mandarin Chinese', 'English', 'Spanish', 'Hindi'],
            ['How many players from one team are on the field in a soccer match?', '11', '9', '10', '12'],
        ],
        // Level 6 - $2,000
        [
            ['Who wrote "Romeo and Juliet"?', 'William Shakespeare', 'Charles Dickens', 'Jane Austen', 'Mark Twain'],
            ['What is the capital of Australia?', 'Canberra', 'Sydney', 'Melbourne', 'Perth'],
            ['Which element has the atomic number 1?', 'Hydrogen', 'Helium', 'Oxygen', 'Carbon'],
        ],
        // Level 7 - $4,000
        [
            ['In what year did the Titanic sink?', '1912', '1905', '1918', '1921'],
            ['What is the smallest prime number?', '2', '0', '1', '3'],
            ['Which country gave the Statue of Liberty to the United States?', 'France', 'England', 'Spain', 'Italy'],
        ],
        // Level 8 - $8,000
        [
            ['What is the longest river in Africa?', 'Nile', 'Congo', 'Niger', 'Zambezi'],
            ['Who developed the theory of general relativity?', 'Albert Einstein', 'Isaac Newton', 'Niels Bohr', 'Galileo Galilei'],
            ['What is the currency of Japan?', 'Yen', 'Won', 'Yuan', 'Ringgit'],
        ],
        // Level 9 - $16,000
        [
            ['Which ancient civilization built Machu Picchu?', 'Inca', 'Aztec', 'Maya', 'Olmec'],
            ['Which vitamin does the body produce when skin is exposed to sunlight?', 'Vitamin D', 'Vitamin A', 'Vitamin C', 'Vitamin K'],
            ['In computing, what does "CPU" stand for?', 'Central Processing Unit', 'Central Program Utility', 'Computer Personal Unit', 'Core Processing Utility'],
        ],
        // Level 10 - $32,000 (safe haven)
        [
            ['Who was the first person to walk on the Moon?', 'Neil Armstrong', 'Buzz Aldrin', 'Yuri Gagarin', 'John Glenn'],
            ['Which country has the most time zones, counting its overseas territories?', 'France', 'Russia', 'United States', 'China'],
            ['What is the capital city of Turkey?', 'Ankara', 'Istanbul', 'Izmir', 'Bursa'],
        ],
        // Level 11 - $64,000
        [
            ['Which scientist is credited with discovering penicillin?', 'Alexander Fleming', 'Louis Pasteur', 'Marie Curie', 'Joseph Lister'],
            ['What is the smallest country in the world by area?', 'Vatican City', 'Monaco', 'San Marino', 'Liechtenstein'],
            ['In which year did the Berlin Wall fall?', '1989', '1985', '1991', '1987'],
        ],
        // Level 12 - $125,000
        [
            ['Which Russian author wrote "Crime and Punishment"?', 'Fyodor Dostoevsky', 'Leo Tolstoy', 'Anton Chekhov', 'Ivan Turgenev'],
            ['What is the most abundant gas in Earth\'s atmosphere?', 'Nitrogen', 'Oxygen', 'Argon', 'Carbon dioxide'],
            ['Which empire was founded by Genghis Khan?', 'Mongol Empire', 'Ottoman Empire', 'Persian Empire', 'Byzantine Empire'],
        ],
        // Level 13 - $250,000
        [
            ['In 1994, Andrew Wiles proved a famous "last theorem" first stated by which mathematician?', 'Pierre de Fermat', 'Leonhard Euler', 'Carl Friedrich Gauss', 'Blaise Pascal'],
            ['What is the deepest known point in Earth\'s oceans?', 'Challenger Deep', 'Puerto Rico Trench', 'Java Trench', 'Kermadec Trench'],
            ['Which Roman emperor issued the Edict of Milan in 313, legalizing Christianity?', 'Constantine', 'Nero', 'Augustus', 'Diocletian'],
        ],
        // Level 14 - $500,000
        [
            ['Which element has the chemical symbol "W"?', 'Tungsten', 'Tantalum', 'Titanium', 'Vanadium'],
            ['Which 1648 treaty ended the Thirty Years\' War?', 'Peace of Westphalia', 'Treaty of Versailles', 'Treaty of Utrecht', 'Treaty of Tordesillas'],
            ['Which physicist discovered the electron in 1897?', 'J. J. Thomson', 'Ernest Rutherford', 'Niels Bohr', 'James Chadwick'],
        ],
        // Level 15 - $1,000,000
        [
            ['Which letter does not appear in the name of any US state?', 'Q', 'J', 'X', 'Z'],
            ['What is the SI base unit of luminous intensity?', 'Candela', 'Lumen', 'Lux', 'Kelvin'],
            ['Which Indian mathematician famously collaborated with G. H. Hardy at Cambridge?', 'Srinivasa Ramanujan', 'Subrahmanyan Chandrasekhar', 'C. V. Raman', 'Harish-Chandra'],
        ],
    ];
}

/**
 * Smart Shuffle: pick a random question for a difficulty level, skipping any
 * question already asked in this game. If a level runs out, it resets.
 */
function pick_question(int $levelIndex, array &$asked): array
{
    $pool = question_bank()[$levelIndex];

    $available = [];
    foreach ($pool as $i => $_) {
        if (!in_array($levelIndex . '-' . $i, $asked, true)) {
            $available[] = $i;
        }
    }
    if (!$available) {                       // level exhausted -> allow repeats again
        $asked = array_values(array_filter($asked, function ($id) use ($levelIndex) {
            return strpos($id, $levelIndex . '-') !== 0;
        }));
        $available = array_keys($pool);
    }

    $i = $available[random_int(0, count($available) - 1)];
    $asked[] = $levelIndex . '-' . $i;

    [$text, $correct, $w1, $w2, $w3] = $pool[$i];
    $answers = [$correct, $w1, $w2, $w3];
    shuffle($answers);

    $opts = [];
    $correctLetter = 'A';
    foreach ($answers as $n => $ans) {
        $opts[LETTERS[$n]] = $ans;
        if ($ans === $correct) {
            $correctLetter = LETTERS[$n];
        }
    }

    return [
        'text'    => $text,
        'opts'    => $opts,
        'correct' => $correctLetter,          // never sent to the browser
        'removed' => [],                      // letters hidden by 50/50
        'hint'    => null,                    // Phone-a-friend message
    ];
}

/* ---------- 4. Game setup ---------- */
function configured_players(?int $override): array
{
    $first = clean_name($_SESSION['username'] ?? '', 'Player 1');

    // $_SESSION['players'] may be a count (2) or a list of names (ex: ['Alexa', 'Usra'])
    $given = [];
    $count = 1;
    if (isset($_SESSION['players'])) {
        $v = $_SESSION['players'];
        if (is_array($v)) {
            $given = array_values($v);
            $count = count($given);
        } else {
            $count = (int)$v;
        }
    }
    if ($override !== null) {
        $count = $override;
    }
    $count = max(1, min(MAX_PLAYERS, $count));

    $names = [];
    for ($i = 0; $i < $count; $i++) {
        $fallback = $i === 0 ? $first : 'Player ' . ($i + 1);
        $item = $given[$i] ?? '';
        if (is_array($item)) {
            $item = $item['username'] ?? $item['name'] ?? '';
        }
        $names[] = clean_name($item, $fallback);
    }
    return $names;
}

function new_game(?int $override = null): void
{
    $players = [];
    foreach (configured_players($override) as $name) {
        $players[] = [
            'name'      => $name,
            'level'     => 0,        // questions answered correctly
            'score'     => 0,        // money currently won
            'alive'     => true,
            'finished'  => false,    // cleared all 15 questions
            'lifelines' => ['fifty' => true, 'phone' => true],
        ];
    }
    $asked   = [];
    $current = pick_question(0, $asked);

    $_SESSION['game'] = [
        'players' => $players,
        'turn'    => 0,              // index of the player whose turn it is
        'turn_no' => 1,
        'phase'   => 'question',     // question | ladder | over
        'asked'   => $asked,
        'current' => $current,
        'last'    => null,
        'saved'   => false,
    ];
    $_SESSION['score'] = 0;
    $_SESSION['round'] = 1;
}

function next_alive(array $players, int $from): ?int
{
    $n = count($players);
    for ($step = 1; $step <= $n; $step++) {
        $idx = ($from + $step) % $n;
        if ($players[$idx]['alive']) {
            return $idx;
        }
    }
    return null;
}

/* New game on request, or if none exists yet */
if (isset($_GET['new']) || empty($_SESSION['game'])) {
    $override = isset($_GET['players']) ? (int)$_GET['players'] : null;
    new_game($override);
    go('game.php');
}

$g =& $_SESSION['game'];

/* ---------- 5. Form processing (POST -> redirect) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($g['phase'] === 'ladder' && isset($_POST['next'])) {
        // Pass the turn to the next player who is still in the game
        $nxt = next_alive($g['players'], $g['turn']);
        if ($nxt !== null) {
            $g['turn']    = $nxt;
            $g['turn_no']++;
            $g['current'] = pick_question($g['players'][$nxt]['level'], $g['asked']);
            $g['phase']   = 'question';
            $_SESSION['round'] = $g['turn_no'];
            $_SESSION['score'] = $g['players'][$nxt]['score'];
        }

    } elseif ($g['phase'] === 'question') {
        $p =& $g['players'][$g['turn']];
        $q =& $g['current'];

        if (isset($_POST['lifeline'])) {
            $which = $_POST['lifeline'];

            if ($which === 'fifty' && $p['lifelines']['fifty']) {
                $wrong = array_values(array_diff(LETTERS, [$q['correct']], $q['removed']));
                shuffle($wrong);
                $q['removed'] = array_merge($q['removed'], array_slice($wrong, 0, 2));
                $p['lifelines']['fifty'] = false;

            } elseif ($which === 'phone' && $p['lifelines']['phone']) {
                $left   = array_values(array_diff(LETTERS, $q['removed']));
                $wrong  = array_values(array_diff($left, [$q['correct']]));
                $chance = max(40, 95 - 4 * $p['level']);        // friend gets less reliable
                $guess  = (random_int(1, 100) <= $chance || !$wrong)
                    ? $q['correct'] : $wrong[random_int(0, count($wrong) - 1)];
                $q['hint'] = 'Your friend thinks the answer is ' . $guess . '.';
                $p['lifelines']['phone'] = false;
            }

        } elseif (isset($_POST['answer'])) {
            $letter = strtoupper((string)$_POST['answer']);

            // Validate: must be one of A-D and not removed by 50/50
            if (in_array($letter, LETTERS, true) && !in_array($letter, $q['removed'], true)) {
                $correct = ($letter === $q['correct']);

                if ($correct) {
                    $p['level']++;
                    $p['score'] = PRIZES[$p['level'] - 1];
                    if ($p['level'] >= count(PRIZES)) {
                        $p['alive']    = false;
                        $p['finished'] = true;
                    }
                } else {
                    $p['alive'] = false;
                    $p['score'] = safe_prize($p['level']);
                }

                $g['last'] = [
                    'name'     => $p['name'],
                    'correct'  => $correct,
                    'letter'   => $q['correct'],
                    'answer'   => $q['opts'][$q['correct']],
                    'score'    => $p['score'],
                    'level'    => $p['level'],
                    'finished' => $p['finished'],
                ];

                $anyAlive = false;
                foreach ($g['players'] as $pl) {
                    $anyAlive = $anyAlive || $pl['alive'];
                }
                $g['phase'] = $anyAlive ? 'ladder' : 'over';

                $_SESSION['score'] = $p['score'];
                $_SESSION['round'] = $g['turn_no'];

                // Game finished: store every player's result once
                if ($g['phase'] === 'over' && !$g['saved']) {
                    $allSaved = true;
                    foreach ($g['players'] as $pl) {
                        $allSaved = save_score($pl['name'], $pl['score'], count($g['players'])) && $allSaved;
                    }
                    $g['saved']       = true;
                    $g['save_failed'] = !$allSaved;
                }
            }
        }
        unset($p, $q);
    }
    go('game.php');
}

/* ---------- 6. Prepare the view ---------- */
$players  = $g['players'];
$phase    = $g['phase'];
$cur      = $g['players'][$g['turn']];
$nextIdx  = ($phase === 'ladder') ? next_alive($players, $g['turn']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Millionaire Challenge - Game</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="stage">
    <div class="deco" aria-hidden="true">
        <div class="side side-left"><span class="qm">?</span><span class="buzzer"></span></div>
        <div class="side side-right"><span class="qm">?</span><span class="buzzer"></span></div>
        <div class="floor"></div>
    </div>

<?php if ($phase === 'question'): ?>
    <?php
        $q        = $g['current'];
        $qNumber  = $cur['level'] + 1;
        $removed  = $q['removed'];
    ?>
    <main class="marquee">
        <form class="screen question-screen" method="post" action="game.php">
            <div class="q-top">
                <button class="pill<?= $cur['lifelines']['phone'] ? '' : ' used' ?>" type="submit"
                        name="lifeline" value="phone" formnovalidate
                        title="Phone a friend"<?= $cur['lifelines']['phone'] ? '' : ' disabled' ?>>Lifeline</button>
                <span class="q-num" aria-label="Question <?= $qNumber ?>"><?= $qNumber ?></span>
                <button class="pill<?= $cur['lifelines']['fifty'] ? '' : ' used' ?>" type="submit"
                        name="lifeline" value="fifty" formnovalidate
                        title="Remove two wrong answers"<?= $cur['lifelines']['fifty'] ? '' : ' disabled' ?>>50/50</button>
            </div>

            <p class="q-meta">
                Turn <?= (int)$g['turn_no'] ?> &middot; <?= h($cur['name']) ?> &middot; playing for <?= money(PRIZES[$qNumber - 1]) ?>
            </p>

            <h1 class="q-text"><?= h($q['text']) ?></h1>

            <p class="q-hint" role="status"><?= $q['hint'] ? h($q['hint']) : '' ?></p>

            <div class="answers">
                <?php foreach ($q['opts'] as $letter => $text): ?>
                    <?php if (in_array($letter, $removed, true)): ?>
                        <span class="pill gone" aria-hidden="true"></span>
                    <?php else: ?>
                        <button class="pill" type="submit" name="answer" value="<?= $letter ?>">
                            <span class="letter"><?= $letter ?>.</span> <?= h($text) ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </form>
    </main>

    <nav class="corner-bar">
        <a class="pill" href="logout.php?from=game">Logout</a>
    </nav>

<?php else: ?>
    <?php
        $last = $g['last'];
        $dots = [];                                   // rung number => list of player classes
        foreach ($players as $i => $pl) {
            if ($pl['level'] > 0) {
                $dots[$pl['level']][] = 'p' . ($i + 1);
            }
        }
    ?>
    <main class="marquee">
        <section class="screen ladder-screen">

            <div class="ladder-left">
                <span class="pill turn-pill">Turn <?= (int)$g['turn_no'] ?></span>

                <?php if ($last): ?>
                    <div class="result-box <?= $last['correct'] ? 'good' : 'bad' ?>" role="status">
                        <?php if ($last['finished']): ?>
                            <strong>Millionaire!</strong>
                            <?= h($last['name']) ?> won <?= money($last['score']) ?>!
                        <?php elseif ($last['correct']): ?>
                            <strong>Correct!</strong>
                            <?= h($last['name']) ?> now has <?= money($last['score']) ?>.
                            <?php if (in_array($last['level'], SAFE_LEVELS, true)): ?>
                                <small>Safe haven reached.</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <strong>Wrong!</strong>
                            The answer was <?= h($last['letter']) ?>. <?= h($last['answer']) ?>.<br>
                            <?= h($last['name']) ?> leaves with <?= money($last['score']) ?>.
                        <?php endif; ?>
                        <?php if ($phase === 'over'): ?>
                            <small>Game over - see the leaderboard.</small>
                            <?php if (!empty($g['save_failed'])): ?>
                                <small>Note: scores could not be written to the server file; they are kept for this session only.</small>
                            <?php endif; ?>
                        <?php elseif ($nextIdx !== null): ?>
                            <small>Up next: <?= h($players[$nextIdx]['name']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php $p1 = $players[0]; ?>
                <span class="pill player-pill<?= !$p1['alive'] ? ' out' : '' ?><?= $nextIdx === 0 ? ' active' : '' ?>">
                    <?= h($p1['name']) ?>
                    <small><?= money($p1['score']) ?></small>
                </span>
            </div>

            <ol class="ladder" aria-label="Money ladder">
                <?php for ($i = 0; $i < count(PRIZES); $i++):
                    $rung = count(PRIZES) - $i;                 // 15 at the top, 1 at the bottom
                    $safe = in_array($rung, SAFE_LEVELS, true);
                    $here = $dots[$rung] ?? [];
                ?>
                    <li class="rung<?= $safe ? ' safe' : '' ?><?= $here ? ' reached' : '' ?>"
                        style="--f: <?= round($i / (count(PRIZES) - 1), 3) ?>">
                        <?php foreach ($here as $cls): ?><span class="dot <?= $cls ?>"></span><?php endforeach; ?>
                        <?= money(PRIZES[$rung - 1]) ?>
                    </li>
                <?php endfor; ?>
            </ol>

            <div class="ladder-right">
                <?php if (isset($players[1])): $p2 = $players[1]; ?>
                    <span class="pill player-pill<?= !$p2['alive'] ? ' out' : '' ?><?= $nextIdx === 1 ? ' active' : '' ?>">
                        <?= h($p2['name']) ?>
                        <small><?= money($p2['score']) ?></small>
                    </span>
                <?php endif; ?>
            </div>

        </section>
    </main>

    <nav class="corner-bar">
        <a class="pill" href="logout.php?from=game">Logout</a>
        <?php if ($phase === 'over'): ?>
            <a class="pill" href="leaderboard.php">Leaderboard</a>
        <?php else: ?>
            <form method="post" action="game.php">
                <button class="pill" type="submit" name="next" value="1">Next Question</button>
            </form>
        <?php endif; ?>
    </nav>
<?php endif; ?>
</div>
</body>
</html>
