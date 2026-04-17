# Değişiklik Günlüğü

Bu projedeki önemli değişiklikler burada belgelenir.

## [0.1.0-beta] - 2026-04-17

### Eklenenler
- 16 ödeme sağlayıcı entegrasyonu (NestPay, Garanti, PosNet, PayFor, Vakıfbank, KuveytTürk, PayTR, iyzico, Moka, Sipay, Param, Tosla, Craftgate, EsnekPOS, Paratika, Lidio)
- Framework-bağımsız çekirdek, isteğe bağlı Laravel entegrasyonu
- Otomatik sağlayıcı eşleme ile 3D Secure geri dönüş yönetimi
- Akıcı ve değişmez PaymentRequestBuilder
- İşlem durum makinesi (TransactionStateMachine)
- Loglama ve yeniden deneme dekoratörleri
- Doğrulama hattı (ValidationPipeline)
- Kapsamlı test paketi (73 test, 119 doğrulama)
- PHPStan seviye 8 tam uyumluluk

### Güvenlik
- Tüm sağlayıcılarda fail-closed 3DS geri dönüş doğrulaması
- CreditCard __debugInfo ile hassas veri maskeleme
- Olay ve veritabanı için hassas veri redaksiyonu (SensitiveDataRedactor)
- İdempotent olmayan işlemlerde yeniden deneme koruması
- Canlı ortamda SSL doğrulaması zorunluluğu
