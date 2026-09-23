<?php
declare(strict_types=1);

function store_uploaded_file(array $file, int $agreementId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Dosya yüklenemedi.');
    }

    $maxBytes = (int) env('UPLOAD_MAX_MB', 8) * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Dosya boyutu izin verilen sınırı aşıyor.');
    }

    $original = (string) ($file['name'] ?? 'dosya');
    $extension = mb_strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowedExtensions = array_filter(array_map('trim', explode(',', (string) env('ALLOWED_UPLOAD_EXTENSIONS', 'jpg,jpeg,png,webp,pdf'))));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Bu dosya türüne izin verilmiyor.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']) ?: 'application/octet-stream';
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Dosya içeriği güvenli bir tür olarak doğrulanamadı.');
    }

    $dir = dirname(__DIR__, 2) . '/uploads/agreements/' . $agreementId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Yükleme klasörü oluşturulamadı.');
    }

    $stored = random_token(20) . '.' . $extension;
    $target = $dir . '/' . $stored;
    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        throw new RuntimeException('Dosya sunucuya taşınamadı.');
    }

    return [
        'original_name' => mb_substr($original, 0, 180),
        'stored_name' => $agreementId . '/' . $stored,
        'mime_type' => $mime,
        'size_bytes' => filesize($target) ?: 0,
        'sha256' => hash_file('sha256', $target),
    ];
}
