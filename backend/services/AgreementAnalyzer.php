<?php
declare(strict_types=1);

final class AgreementAnalyzer
{
    public static function analyze(string $type, string $rawText, array $explicit): array
    {
        $rawText = trim($rawText);
        $clauses = [];

        self::push($clauses, 'work', 'Ne yapılacak?', $explicit['description'] ?? $rawText, 'user');
        self::push($clauses, 'payment', 'Ödeme', $explicit['payment_terms'] ?? '', 'user');
        self::push($clauses, 'included', 'Fiyata dahil olanlar', $explicit['included_items'] ?? '', 'user');
        self::push($clauses, 'excluded', 'Fiyata dahil olmayanlar', $explicit['excluded_items'] ?? '', 'user');
        self::push($clauses, 'revisions', 'Değişiklik hakkı', $explicit['revision_terms'] ?? '', 'user');
        self::push($clauses, 'delivery', 'Teslim ve tarih', self::dateText($explicit), 'user');
        self::push($clauses, 'cancellation', 'İptal veya aksama durumu', $explicit['cancellation_terms'] ?? '', 'user');
        self::push($clauses, 'notes', 'Diğer notlar', $explicit['notes'] ?? '', 'user');

        if (($explicit['total_amount'] ?? '') !== '') {
            self::push($clauses, 'amount', 'Toplam tutar', money((string) $explicit['total_amount'], 'TRY'), 'user');
        }

        $missing = self::missingRadar($type, $clauses, $rawText);
        $risk = self::riskLevel($type, $rawText . ' ' . json_encode($explicit, JSON_UNESCAPED_UNICODE));
        $ambiguities = self::ambiguities($rawText);

        return [
            'clauses' => $clauses,
            'missing' => $missing,
            'ambiguities' => $ambiguities,
            'risk_level' => $risk,
            'raw_text' => $rawText,
            'analyzer' => 'local-rule-engine-v1',
        ];
    }

    private static function push(array &$clauses, string $key, string $title, string $content, string $source): void
    {
        $content = trim($content);
        if ($content !== '') {
            $clauses[] = compact('key', 'title', 'content', 'source');
        }
    }

    private static function dateText(array $explicit): string
    {
        $parts = [];
        if (!empty($explicit['start_date'])) {
            $parts[] = 'Başlangıç: ' . format_date((string) $explicit['start_date']);
        }
        if (!empty($explicit['end_date'])) {
            $parts[] = 'Bitiş/teslim: ' . format_date((string) $explicit['end_date']);
        }
        return implode(' · ', $parts);
    }

    private static function missingRadar(string $type, array $clauses, string $rawText): array
    {
        $present = array_column($clauses, 'key');
        $rules = [
            'service' => [
                'payment' => 'Ödemenin ne zaman ve kaç parça yapılacağı',
                'delivery' => 'Net teslim tarihi',
                'revisions' => 'Kaç revizyonun dahil olduğu',
                'included' => 'Fiyata dahil olan işler veya malzemeler',
                'excluded' => 'Fiyata dahil olmayan işler',
            ],
            'purchase_service' => [
                'payment' => 'Ödeme planı',
                'delivery' => 'İşe başlama ve bitiş tarihi',
                'included' => 'Malzeme ve işçilik kapsamı',
                'cancellation' => 'Ek iş veya gecikme çıkarsa ne olacağı',
            ],
            'sale' => [
                'work' => 'Ürünün marka, model ve mevcut durumu',
                'payment' => 'Ödeme yöntemi',
                'delivery' => 'Teslim yeri ve zamanı',
                'notes' => 'Bilinen kusurlar ve eksik aksesuarlar',
            ],
            'debt' => [
                'amount' => 'Borç tutarı',
                'delivery' => 'Geri ödeme tarihi',
                'payment' => 'Tek sefer veya taksit bilgisi',
            ],
            'loan_item' => [
                'work' => 'Ödünç verilen eşya ve aksesuarları',
                'delivery' => 'İade tarihi',
                'notes' => 'Eşyanın mevcut durumu',
            ],
            'shared_expense' => [
                'amount' => 'Kişi başı tutar',
                'payment' => 'Kimin ne zaman ödeyeceği',
                'cancellation' => 'Bir kişi vazgeçerse ne olacağı',
            ],
            'deposit' => [
                'amount' => 'Kapora tutarı',
                'delivery' => 'Hizmet veya etkinlik tarihi',
                'cancellation' => 'İptal ve iade şartı',
            ],
        ];

        $missing = [];
        foreach ($rules[$type] ?? [] as $key => $message) {
            if (!in_array($key, $present, true)) {
                $missing[] = $message;
            }
        }

        if (!preg_match('/\b(telefon|e-posta|email|whatsapp|elden|iban|banka)\b/ui', $rawText)) {
            $missing[] = 'İletişim veya ödeme yöntemi konuşuldu mu?';
        }

        return array_values(array_unique($missing));
    }

    private static function ambiguities(string $text): array
    {
        $items = [];
        $patterns = [
            '/\b(yakında|sonra|müsait olunca|en kısa sürede)\b/ui' => 'Belirsiz zaman ifadesi var; belirli bir tarih yazın.',
            '/\b(uygun bir fiyat|makul ücret|gerektiği kadar)\b/ui' => 'Belirsiz ücret veya kapsam ifadesi var.',
            '/\b(halledilir|bakarız|konuşuruz)\b/ui' => 'Sonradan farklı yorumlanabilecek bir ifade var.',
        ];
        foreach ($patterns as $pattern => $message) {
            if (preg_match($pattern, $text)) {
                $items[] = $message;
            }
        }
        return $items;
    }

    private static function riskLevel(string $type, string $text): string
    {
        $red = ['taşınmaz', 'tapu', 'araç devri', 'vasiyet', 'kefalet', 'tıbbi onam', 'hisse devri', 'uyuşturucu', 'silah', 'bahis'];
        foreach ($red as $word) {
            if (mb_stripos($text, $word) !== false) {
                return 'red';
            }
        }
        if (in_array($type, ['debt', 'deposit'], true) || preg_match('/\b([1-9][0-9]{5,})\b/u', str_replace(['.', ','], '', $text))) {
            return 'yellow';
        }
        return 'green';
    }
}
