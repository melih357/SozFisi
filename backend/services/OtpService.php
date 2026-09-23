<?php
declare(strict_types=1);

final class OtpService
{
    public static function issue(int $agreementId, ?int $partyId, string $purpose): string
    {
        $code = (string) random_int(100000, 999999);
        $hash = self::hash($code, $agreementId, $purpose);
        $ttl = max(1, (int) env('OTP_TTL_MINUTES', 5));

        $stmt = db()->prepare('INSERT INTO otp_codes (agreement_id, party_id, purpose, code_hash, expires_at, attempt_count, created_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0, NOW())');
        $stmt->execute([$agreementId, $partyId, $purpose, $hash, $ttl]);

        return $code;
    }

    public static function verify(int $agreementId, ?int $partyId, string $purpose, string $code): ?array
    {
        $stmt = db()->prepare('SELECT * FROM otp_codes WHERE agreement_id = ? AND party_id <=> ? AND purpose = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1');
        $stmt->execute([$agreementId, $partyId, $purpose]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        if ((int) $row['attempt_count'] >= 5 || strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        db()->prepare('UPDATE otp_codes SET attempt_count = attempt_count + 1 WHERE id = ?')->execute([(int) $row['id']]);
        if (!hash_equals((string) $row['code_hash'], self::hash($code, $agreementId, $purpose))) {
            return null;
        }

        db()->prepare('UPDATE otp_codes SET consumed_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
        return $row;
    }

    private static function hash(string $code, int $agreementId, string $purpose): string
    {
        return hash_hmac('sha256', $agreementId . '|' . $purpose . '|' . $code, (string) env('APP_KEY', 'local-key'));
    }
}
