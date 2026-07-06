<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/db.php';

session_name('stm_admin');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

function admin_exists(): bool
{
    try {
        $db = get_db_connection();
        $stmt = $db->query('SELECT COUNT(*) FROM `admin`');
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function current_admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): void
{
    if (!current_admin()) {
        header('Location: index.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function require_csrf(): void
{
    $posted = $_POST['csrf'] ?? '';
    if (!is_string($posted) || !hash_equals(csrf_token(), $posted)) {
        http_response_code(403);
        exit('Invalid security token.');
    }
}

function attempt_login(string $username, string $password): bool
{
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT * FROM `admin` WHERE `username` = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !verify_admin_password($password, $admin)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin'] = ['username' => $username];
    return true;
}

function verify_admin_password(string $password, array $admin): bool
{
    return password_verify($password, $admin['password_hash'] ?? '');
}

function save_admin_password(string $username, string $password): void
{
    $db = get_db_connection();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->query('SELECT `id` FROM `admin` LIMIT 1');
    $existingId = $stmt->fetchColumn();

    if ($existingId !== false) {
        $stmt = $db->prepare('UPDATE `admin` SET `username` = ?, `password_hash` = ? WHERE `id` = ?');
        $stmt->execute([clean_text($username, 80), $hash, $existingId]);
    } else {
        $stmt = $db->prepare('INSERT INTO `admin` (`username`, `password_hash`) VALUES (?, ?)');
        $stmt->execute([clean_text($username, 80), $hash]);
    }
}

function create_admin(string $username, string $password): void
{
    if (admin_exists()) {
        throw new RuntimeException('Admin account already exists.');
    }

    save_admin_password($username, $password);
}
