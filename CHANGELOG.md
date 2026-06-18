# Değişiklik Günlüğü

Bu projedeki önemli değişiklikler burada belgelenir.

## [0.2.0-beta] - 2026-06-18

### Eklenenler
- **Kart saklama / card-on-file** yeteneği (`CardStorageInterface`) — `ThreeDSecureInterface` gibi opsiyonel bir capability. İlk implementasyon: iyzico.
  - `PaymentRequest::$storeCard` (builder `->storeCard()`) ile ödeme sırasında kart tokenize edilir (iyzico `registerCard=1`).
  - Token'lar `PaymentResponse->cardUserKey` + `->cardToken` olarak döner (`IyzicoResponseParser`).
  - `chargeStoredCard(StoredCardChargeRequest)` ile saklı kartla PAN/CVC'siz, non-3DS tahsilat (recurring/abonelik için).
- `StoredCardChargeRequest` DTO.
- `AbstractGateway`: post-serialization `signRequest()` hook'u (gövde+path tabanlı auth için) ve server-to-server 3DS için `threeDUsesServerInitialize()` / `parseThreeDInitialize()` / `threeDProvisionEndpoint()` hook'ları. `ThreeDSecureData`'ya `htmlContent` (server-init modeli).

### Değişenler
- **iyzico kimlik doğrulama v1 PKI (IYZWS) → v2 HMAC (IYZWSv2)**. Güncel iyzico v1 imzayı "Geçersiz imza" ile reddediyordu; server-to-server çağrılar (purchase/refund/cancel/status/chargeStoredCard) artık `HMAC-SHA256(randomKey + uriPath + body, secretKey)` imzalı.
- **iyzico 3DS artık server-to-server**: form-POST yerine `/payment/3dsecure/initialize` çağrılıp hazır `threeDSHtmlContent` döndürülüyor; tamamlama `/payment/3dsecure/auth`'a gidiyor; callback doğrulaması `paymentId`+`conversationId` üzerinden.
- `AbstractGateway::executeTransaction()` artık `protected` + opsiyonel `endpointOverride` (capability metotları ve 3DS provision için).

### Düzeltmeler
- Sayısal sepet/parametre anahtarlarında `str_starts_with()` int-key `TypeError`'ı (regular ödemede "bağlantı hatası" olarak görünüyordu).
- iyzico server-to-server çağrılarda eksik `Authorization` header'ı.

### Test
- Test paketi 156 test / 496 doğrulama (iyzico kart saklama + v2 auth + 3DS server-init dahil). PHPStan seviye 8 temiz. iyzico sandbox'ta canlı doğrulandı: non-3DS ödeme başarılı, 3DS initialize gerçek HTML döndürüyor.

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
