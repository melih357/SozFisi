<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(): string
{
    return rtrim((string) env('APP_URL', 'http://localhost/sozfisi'), '/');
}

function url(string $path = '', array $query = []): string
{
    $base = app_url();
    $path = ltrim($path, '/');
    $full = $path === '' ? $base : $base . '/' . $path;
    return $query === [] ? $full : $full . (str_contains($full, '?') ? '&' : '?') . http_build_query($query);
}

function page_url(string $page, array $query = []): string
{
    return url('frontend/index.php', array_merge(['page' => $page], $query));
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($flashes) ? $flashes : [];
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function random_token(int $bytes = 24): string
{
    return bin2hex(random_bytes($bytes));
}

function public_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = 'SF-';
    for ($i = 0; $i < 6; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $code;
}

function request_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash_hmac('sha256', $ip, (string) env('APP_KEY', 'local-key'));
}

function request_agent_hash(): string
{
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    return hash_hmac('sha256', $agent, (string) env('APP_KEY', 'local-key'));
}

function money(?string $amount, string $currency = 'TRY'): string
{
    if ($amount === null || $amount === '') {
        return 'Belirtilmedi';
    }
    $formatted = number_format((float) $amount, 2, ',', '.');
    return $currency === 'TRY' ? $formatted . ' TL' : $formatted . ' ' . $currency;
}

function format_date(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return 'Belirtilmedi';
    }
    try {
        $dt = new DateTimeImmutable($date);
        return $dt->format($withTime ? 'd.m.Y H:i' : 'd.m.Y');
    } catch (Throwable) {
        return e($date);
    }
}

function status_label(string $status): string
{
    return [
        'draft' => 'Taslak', 'sent' => 'Gönderildi', 'viewed' => 'Görüldü',
        'changes_requested' => 'Değişiklik istendi', 'ready' => 'Onaya hazır',
        'approved' => 'Onaylandı', 'active' => 'Aktif',
        'completion_pending' => 'Tamamlama bekliyor', 'completed' => 'Tamamlandı',
        'cancelled' => 'İptal edildi', 'disputed' => 'Sorun bildirildi',
        'archived' => 'Arşivlendi',
    ][$status] ?? ucfirst($status);
}

function status_class(string $status): string
{
    return match ($status) {
        'approved', 'active', 'completed' => 'success',
        'changes_requested', 'completion_pending' => 'warning',
        'disputed', 'cancelled' => 'danger',
        'sent', 'viewed', 'ready' => 'info',
        default => 'neutral',
    };
}

function type_label(string $type): string
{
    return [
        'service' => 'Müşteri işi', 'purchase_service' => 'Bir iş yaptırıyorum',
        'sale' => 'İkinci el satış', 'debt' => 'Borç', 'loan_item' => 'Eşya ödünç',
        'shared_expense' => 'Ortak masraf', 'deposit' => 'Kapora', 'other' => 'Başka anlaşma',
    ][$type] ?? 'Anlaşma';
}

function json_decode_array(?string $json): array
{
    if (!$json) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
