<?php
declare(strict_types=1);

function lifeflow_env(string $name, ?string $default = null): ?string {
    $value = getenv($name);
    if ($value === false) return $default;
    $value = trim($value);
    return $value === '' ? $default : $value;
}

$db_host = lifeflow_env('DB_HOST', '127.0.0.1');
$db_name = lifeflow_env('DB_NAME', 'lifeflow');
$db_user = lifeflow_env('DB_USER', 'root');
$db_pass = lifeflow_env('DB_PASS', '');
$db_port = (int)(lifeflow_env('DB_PORT', '3306') ?? '3306');

try {
    $pdo = new PDO(
        "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>LifeFlow - DB Error</title>
    <style>body{font-family:Inter,sans-serif;background:#fef2f2;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .card{background:#fff;border:1px solid #fecdd3;border-radius:16px;padding:2rem;max-width:600px;text-align:center}
    h1{color:#b22234}code{background:#fee2e2;padding:2px 6px;border-radius:4px}</style></head>
    <body><div class="card"><h1>&#128197; Database Connection Failed</h1>
    <p>Could not connect to MySQL. Please import <code>database.sql</code> and check <code>db_connect.php</code>.</p>
    <p><strong>Error:</strong> {$msg}</p></div></body></html>
    HTML;
    exit;
}

function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: {$url}");
    exit;
}

// --- CSRF Protection Helpers ---
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

function verify_csrf_token(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    $postToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    
    if (empty($postToken) || empty($sessionToken) || !hash_equals($sessionToken, $postToken)) {
        return false;
    }
    return true;
}

