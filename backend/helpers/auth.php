<?php
declare(strict_types=1);

function user(): ?array
{
    static $cached = false;
    static $current = null;
    if ($cached) {
        return $current;
    }
    $cached = true;

    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, phone, role, plan, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);
    $current = $stmt->fetch() ?: null;
    return $current;
}

function auth_check(): bool
{
    return user() !== null;
}

function require_auth(): void
{
    if (!auth_check()) {
        flash('warning', 'Bu sayfayı açmak için giriş yapmalısınız.');
        redirect(page_url('login'));
    }
}

function require_guest(): void
{
    if (auth_check()) {
        redirect(page_url('dashboard'));
    }
}

function require_admin(): void
{
    require_auth();
    if ((user()['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        exit('Bu alanı görüntüleme yetkiniz yok.');
    }
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
