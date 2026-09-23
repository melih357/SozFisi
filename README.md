# SözFişi — WAMP Server MVP v1

SözFişi, iki kişinin konuşarak veya mesajlaşarak vardığı küçük anlaşmaları; karşılıklı kontrol edilen, sürümlenen, OTP ile onaylanabilen, takip edilen ve tamamlanabilen ortak bir işlem kaydına dönüştüren mobil uyumlu PHP/MySQL web uygulamasıdır.

Bu paket özellikle **WAMP Server** için hazırlanmıştır. Docker, Node.js veya Composer kurulumu gerekmez.

## Giriş adresi

Bu sürüm RoadPass ile aynı klasör mantığında çalışır. Uygulamayı doğrudan şu adresten açın:

```text
http://localhost/sozfisi/frontend/
```

Kök adresi (`http://localhost/sozfisi/`) açarsanız sistem otomatik olarak `frontend/` klasörüne yönlendirir.


## Bu sürümde çalışan özellikler

- Mobil uyumlu kurumsal ana sayfa ve SözFişi marka renkleri
- Kullanıcı kayıt, giriş, çıkış ve profil
- Sekiz anlaşma türü için SözFişi oluşturma
- Tarayıcı destekliyorsa Türkçe sesli anlatım
- Yerel kurallı “eksik koşul radarı” ve belirsiz ifade kontrolü
- Taslak, gönderildi, görüldü, değişiklik istendi, aktif, tamamlandı gibi durumlar
- Karşı tarafın üye olmadan davet bağlantısını açması
- Madde bazında değişiklik talebi
- Değişiklik kabul edilince yeni sürüm ve yeni SHA-256 özeti
- Yerel test OTP akışı
- Onaylanan sürümü dondurma
- Dosya yükleme; JPG, PNG, WEBP ve PDF
- Dosya SHA-256 özeti
- Ek SözFişi oluşturma ve OTP ile onaylama
- Tamamlama / kısmi tamamlama / sorun var kapanış akışı
- İşlem geçmişi
- Yazdırma görünümü ve tarayıcıdan PDF kaydetme
- “Bu anlaşma bana ait değil” bildirimi
- Yönetici paneli ve sistem sağlık kontrolü
- PWA manifesti ve temel statik önbellek

## Gereksinimler

Önerilen yerel ortam:

- Windows 10 veya 11
- WampServer 3.x
- Apache 2.4+
- PHP 8.1 veya üstü
- MySQL 8 veya MariaDB 10.5+
- PHP eklentileri: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`

WAMP içindeki PHP sürümü daha eskiyse PHP 8.1+ sürümüne geçin.

## Klasör yapısı

```text
sozfisi/
├── backend/
│   ├── actions/             Form işlemleri
│   ├── config/              .env ve veritabanı bağlantısı
│   ├── helpers/             Güvenlik, oturum, SözFişi ve yükleme yardımcıları
│   ├── services/            Yerel analiz ve OTP servisleri
│   └── tools/health.php     Sistem sağlık kontrolü
├── frontend/
│   ├── assets/              CSS, JavaScript ve logo görselleri
│   ├── pages/               Sayfalar
│   ├── partials/            Header, footer ve bildirim parçaları
│   ├── manifest.webmanifest
│   └── service-worker.js
├── uploads/                 Kullanıcı dosyaları
├── storage/                 Log/cache için ayrılmış alan
├── .env                     Yerel ayarlar
├── database.sql             Sıfırdan veritabanı kurulumu
├── migration.sql            Sonraki güncellemeler için migration örneği
├── index.php                Uygulama giriş noktası
└── README.md
```

# WAMP üzerinde kurulum

## 1. Projeyi WAMP klasörüne kopyalayın

ZIP dosyasını açın ve `sozfisi` klasörünü şu konuma kopyalayın:

```text
C:\wamp64\www\sozfisi
```

WAMP başka bir klasöre kuruluysa kendi `www` klasörünüzü kullanın.

## 2. WAMP servislerini başlatın

WampServer simgesine tıklayın ve Apache ile MySQL/MariaDB servislerinin yeşil olduğundan emin olun.

## 3. Veritabanını oluşturun

Tarayıcıdan phpMyAdmin’i açın:

```text
http://localhost/phpmyadmin
```

Ardından:

1. **İçe aktar / Import** sekmesini açın.
2. Proje içindeki `database.sql` dosyasını seçin.
3. Çalıştırın.

Dosya `sozfisi` veritabanını ve bütün tabloları otomatik oluşturur.

> `database.sql` mevcut tabloları silerek yeniden kurar. Test veriniz varsa tekrar çalıştırmadan önce yedek alın.

## 4. `.env` dosyasını kontrol edin

Varsayılan WAMP kurulumu için ayarlar hazırdır:

```env
APP_URL=http://localhost/sozfisi/frontend/
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sozfisi
DB_USERNAME=root
DB_PASSWORD=
OTP_DEBUG=true
```

MySQL root hesabınızın şifresi varsa `DB_PASSWORD` alanına yazın.

`APP_KEY` değerini üretimden önce uzun ve rastgele bir değerle değiştirin. Örnek üretmek için PowerShell:

```powershell
-join ((48..57)+(65..90)+(97..122) | Get-Random -Count 64 | ForEach-Object {[char]$_})
```

## 5. Siteyi açın

```text
http://localhost/sozfisi/frontend/
```

Ana sayfa açılıyorsa temel kurulum tamamlanmıştır.

## 6. Sağlık kontrolü

```text
http://localhost/sozfisi/backend/tools/health.php
```

Beklenen sonuç:

```json
{
  "database": "ok",
  "uploads_writable": true
}
```

# Baştan sona test senaryosu

## Test 1 — Kullanıcı hesabı

1. Ana sayfadan **Ücretsiz başla** seçeneğine basın.
2. Ad, e-posta ve en az sekiz karakterli şifre girin.
3. Kayıt sonrası otomatik olarak **SözFişlerim** ekranına yönlendirilmelisiniz.

## Test 2 — İlk SözFişi

1. **Yeni SözFişi** seçeneğine basın.
2. “Müşteriye iş yapıyorum” türünü seçin.
3. Örnek veriler:

```text
Başlık: 3 sosyal medya videosu
Karşı taraf: Mert Yılmaz
Konuşulan: Müşteriye üç dikey sosyal medya videosu hazırlayacağım. Toplam 12.000 TL. Yarısı başta, yarısı teslimde ödenecek. Bir hafta içinde teslim edeceğim.
Toplam tutar: 12000
Ödeme: 6.000 TL başlangıçta, 6.000 TL teslimde.
Revizyon: Her video için bir revizyon.
```

4. Taslağı oluşturun.
5. SözFişi detayında anlaşma kartlarını ve **Eksik koşul radarı** bölümünü kontrol edin.

## Test 3 — Davet bağlantısı

1. Detay ekranında **Karşı tarafa gönder** seçeneğine basın.
2. Oluşan bağlantıyı kopyalayın.
3. Bağlantıyı gizli/Incognito tarayıcı penceresinde açın.
4. Oluşturucu hesabına giriş yapmadan davet sayfası görünmelidir.

## Test 4 — Değişiklik talebi

1. Gizli penceredeki davet sayfasında **Değişiklik istiyorum** seçeneğine basın.
2. Bir madde seçin ve yeni öneri yazın.
3. Normal pencerede oluşturucu hesabına dönün.
4. Detay sayfasında değişiklik talebi görünmelidir.
5. **Kabul et ve yeni sürüm oluştur** seçeneğine basın.
6. Sürüm numarası ve SHA-256 özeti değişmelidir.

## Test 5 — OTP ile onay

1. Gizli pencerede davet sayfasını yenileyin.
2. **Metni okudum ve onaylıyorum** seçeneğine basın.
3. Yerel test modunda 6 haneli OTP kodu ekranda görünür.
4. Kodu kutuya yazıp doğrulayın.
5. SözFişi durumu **Aktif** olmalıdır.

> `OTP_DEBUG=true` yalnızca yerel geliştirme içindir. Canlı ortamda `false` yapılmalı ve gerçek SMS/e-posta sağlayıcısı bağlanmalıdır.

## Test 6 — Dosya yükleme

1. Oluşturucu detay sayfasında JPG, PNG, WEBP veya PDF seçin.
2. Dosyayı yükleyin.
3. Dosya adı, boyutu ve SHA-256 özetinin listelendiğini kontrol edin.
4. Fiziksel dosya `uploads/agreements/{agreement_id}/` klasörüne yazılır.

## Test 7 — Ek SözFişi

1. Oluşturucu ekranından ek iş nedeni ve istenirse ek tutar girin.
2. Ek SözFişi oluşturun.
3. Gizli davet ekranında ek kayıt görünmelidir.
4. OTP ile onaylayın.

## Test 8 — Kapanış

1. Oluşturucu detay sayfasında **İşi tamamladım** seçeneğine basın.
2. Gizli davet sayfasını açın.
3. “Tamamlandı”, “Kısmen tamamlandı” veya “Sorun var” seçeneklerinden birini gönderin.
4. “Tamamlandı” seçildiğinde oluşturucu ekranında kapanış kaydı görünmelidir.

## Test 9 — PDF

1. Detay sayfasında **Yazdır / PDF** seçeneğine basın.
2. Tarayıcının yazdırma penceresinde hedef olarak **PDF olarak kaydet** seçin.
3. Oluşan PDF’de ortak metin, durum, sürüm ve işlem geçmişini kontrol edin.

## Test 10 — Yönetici paneli

Yerel kurulumla gelen test yöneticisi:

```text
E-posta: admin@sozfisi.local
Şifre: Admin123!
```

1. Normal hesaptan çıkış yapın.
2. Bu bilgilerle giriş yapın.
3. Menüde **Yönetim** bağlantısı görünmelidir.
4. “Bu anlaşma bana ait değil” bildirimleri yönetim kuyruğunda listelenir.

> Canlıya çıkmadan önce bu hesabın şifresini değiştirin veya hesabı silin.

# Yerel analiz motoru

Bu pakette dış API gerektirmeyen kurallı analiz vardır:

- Anlaşma türüne göre eksik alan önerir.
- “Yakında”, “sonra”, “halledilir” gibi belirsiz ifadeleri işaretler.
- Taşınmaz devri, resmî araç devri, kefalet, tıbbi onam ve hukuka aykırı içerik gibi kırmızı alanları engellemeye çalışır.
- Borç ve kapora gibi alanları sarı risk olarak gösterir.

Bu motor gerçek yapay zekâ veya hukuk danışmanı değildir. `AI_PROVIDER=local` ayarı bu davranışı belirtir. Sonraki revizyonda haricî yapay zekâ servisi ayrı bir servis sınıfı üzerinden eklenebilir.

# Gerçek SMS eklemek için

Şu anda OTP kodu veritabanında hash’lenir ve yerel modda ekranda gösterilir. Gerçek SMS için:

1. Bir SMS sağlayıcısı seçin.
2. `OtpService::issue()` içinde kod üretildikten sonra sağlayıcı istemcisini çağırın.
3. `.env` içine sağlayıcı anahtarı ve gönderici başlığını ekleyin.
4. `OTP_DEBUG=false` yapın.
5. Aynı numaraya gönderim limiti ve başarısız deneme oran sınırlaması ekleyin.

# Üretime çıkmadan önce zorunlu işler

Bu paket işlevsel bir yerel MVP’dir; doğrudan canlıya yüklenmemelidir. Önce:

- HTTPS zorunlu hâle getirilmeli.
- `.env`, SQL ve README dosyalarının web erişimi sunucuda tekrar doğrulanmalı.
- Gerçek SMS/e-posta doğrulaması bağlanmalı.
- KVKK aydınlatma metni, açık rıza süreçleri ve saklama politikası hukuk uzmanıyla hazırlanmalı.
- Desteklenmeyen işlem listesi hukuk uzmanıyla doğrulanmalı.
- Dosyalarda zararlı yazılım taraması eklenmeli.
- Yüklenen dosyalar mümkünse web kökünün dışında veya özel nesne depolamada tutulmalı.
- Oturum, OTP, davet bağlantısı ve yükleme uçlarına ek rate limit konulmalı.
- E-posta doğrulama, şifre sıfırlama ve hesap silme akışları eklenmeli.
- Düzenli yedekleme ve penetrasyon testi yapılmalı.
- Test yönetici hesabı kaldırılmalı.

# Sık karşılaşılan sorunlar

## “Veritabanı bağlantısı kurulamadı”

- WAMP MySQL servisinin çalıştığını kontrol edin.
- `.env` içindeki kullanıcı adı ve şifreyi kontrol edin.
- phpMyAdmin’de `sozfisi` veritabanının oluştuğunu doğrulayın.

## Türkçe karakterler bozuk

- Veritabanı ve tablolar `utf8mb4_turkish_ci` olmalıdır.
- PHP dosyalarını UTF-8 olarak saklayın.

## Dosya yüklenmiyor

- `uploads` klasörünün yazılabilir olduğundan emin olun.
- WAMP `php.ini` içindeki `upload_max_filesize` ve `post_max_size` değerlerini 8 MB veya daha yüksek yapın.
- Değişiklik sonrası Apache’yi yeniden başlatın.

## Mikrofon çalışmıyor

- Tarayıcının mikrofon iznini kontrol edin.
- Sesli giriş Chromium tabanlı tarayıcılarda daha iyi desteklenir.
- Desteklenmeyen tarayıcıda normal metin girişi kullanılabilir.

## URL farklı klasörde açılıyor

Projeyi farklı klasör adıyla kurduysanız `.env` içindeki `APP_URL` değerini değiştirin:

```env
APP_URL=http://localhost/yeni-klasor-adi
```

# Revize planı için önerilen sıra

1. Ana sayfa, renkler ve mobil tasarım
2. SözFişi oluşturma alanlarının sadeleştirilmesi
3. Dashboard ve detay ekranı
4. Karşı tarafın davet/onay deneyimi
5. Gerçek yapay zekâ bağlantısı
6. Gerçek SMS/e-posta
7. Delil paketi PDF’si ve haricî zaman damgası
8. Plus / Pro özellikleri

---

**Ürün kuralı:** SözFişi tarafların yerine karar vermez; iki tarafın aynı şeyi gördüğünden emin olur.
