<?php
declare(strict_types=1);

$dbFile = __DIR__ . '/data/tasks.sqlite';
$dataDir = dirname($dbFile);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            completed INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Please enable PHP PDO_SQLite.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirectHome(): never
{
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title !== '' && mb_strlen($title) <= 120) {
            $stmt = $pdo->prepare('INSERT INTO tasks (title) VALUES (:title)');
            $stmt->execute(['title' => $title]);
        }
    } elseif ($action === 'toggle') {
        $stmt = $pdo->prepare(
            'UPDATE tasks SET completed = CASE WHEN completed = 1 THEN 0 ELSE 1 END WHERE id = :id'
        );
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
    }

    redirectHome();
}

$tasks = $pdo->query('SELECT id, title, completed FROM tasks ORDER BY completed ASC, id DESC')
             ->fetchAll(PDO::FETCH_ASSOC);
$total = count($tasks);
$completed = count(array_filter($tasks, static fn(array $task): bool => (int) $task['completed'] === 1));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simple Task Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header class="header">
        <div>
            <p class="eyebrow">STAY ORGANIZED</p>
            <h1>My Tasks</h1>
            <p class="subtitle">A simple place to keep track of what matters.</p>
        </div>
        <div class="summary">
            <strong><?= $completed ?>/<?= $total ?></strong>
            <span>completed</span>
        </div>
    </header>

    <form class="add-form" method="post">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="add">
        <label class="sr-only" for="title">New task</label>
        <input id="title" name="title" type="text" maxlength="120"
               placeholder="What needs to be done?" required>
        <button type="submit">Add task</button>
    </form>

    <section class="task-section" aria-labelledby="task-heading">
        <h2 id="task-heading">Your list</h2>
        <?php if ($tasks === []): ?>
            <div class="empty">
                <div class="empty-icon">✓</div>
                <h3>No tasks yet</h3>
                <p>Add your first task above to get started.</p>
            </div>
        <?php else: ?>
            <ul class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <li class="task <?= (int) $task['completed'] === 1 ? 'is-complete' : '' ?>">
                        <form method="post" class="task-main">
                            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                            <button class="check" type="submit"
                                    aria-label="<?= (int) $task['completed'] === 1 ? 'Mark incomplete' : 'Mark complete' ?>">
                                <?= (int) $task['completed'] === 1 ? '✓' : '' ?>
                            </button>
                            <span><?= escape($task['title']) ?></span>
                        </form>
                        <form method="post" class="delete-form"
                              onsubmit="return confirm('Delete this task?');">
                            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                            <button class="delete" type="submit" aria-label="Delete task">×</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <footer>Simple Task Manager · Built with PHP and SQLite</footer>
</main>
</body>
</html>
