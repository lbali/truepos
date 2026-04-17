# TruePos

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lbali/truepos.svg?style=flat-square)](https://packagist.org/packages/lbali/truepos)
[![License](https://img.shields.io/github/license/lbali/truepos?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/lbali/truepos?style=flat-square)](composer.json)

Tüm Türk sanal POS sağlayıcılarını tek bir unified API altında birleştiren, framework-agnostic PHP paketi. Laravel entegrasyonu dahil.

Bir gateway'den diğerine geçmek için tek yapmanız gereken config değiştirmek — kodunuz aynı kalır.

> **Status: Beta** — Use in production after testing with your bank/acquirer credentials. 3DS provider callbacks are validated by hash where available; some providers (PosNet, Iyzico) verify during server-to-server completion.

## Desteklenen Sağlayıcılar

### Banka Sanal POS'ları

| Sağlayıcı | Driver | Config Adı | Bankalar |
|-----------|--------|------------|----------|
| **NestPay (EST)** | `nestpay` | `akbank` `isbank` `ziraat` `halkbank` `teb` `denizbank` | Akbank, İş Bankası, Ziraat, Halkbank, TEB, Denizbank, Anadolubank, ING |
| **Garanti GVP** | `garanti` | `garanti` | Garanti BBVA |
| **PosNet** | `posnet` | `yapikredi` | Yapı Kredi |
| **PayFor** | `payfor` | `qnbfinansbank` | QNB Finansbank |
| **Vakıfbank VPOS** | `vakifbank` | `vakifbank` | Vakıfbank |
| **Kuveyt Türk** | `kuveytturk` | `kuveytturk` | Kuveyt Türk |

### Ödeme Kuruluşları

| Sağlayıcı | Driver | Config Adı |
|-----------|--------|------------|
| **iyzico** | `iyzico` | `iyzico` |
| **Moka** | `moka` | `moka` |
| **Sipay** | `sipay` | `sipay` |
| **Param** | `param` | `param` |
| **PayTR** | `paytr` | `paytr` |
| **Tosla** | `tosla` | `tosla` |
| **EsnekPOS** | `esnekpos` | `esnekpos` |
| **Paratika** | `paratika` | `paratika` |
| **Lidio** | `lidio` | `lidio` |

### Orkestratör

| Sağlayıcı | Driver | Config Adı |
|-----------|--------|------------|
| **Craftgate** | `craftgate` | `craftgate` |

### Desteklenen Özellikler

| Sağlayıcı | Satış | 3D Secure | 3D Pay | 3D Host | Taksit | Ön Provizyon | İade | İptal | Sorgu |
|-----------|:-----:|:---------:|:------:|:-------:|:------:|:------------:|:----:|:-----:|:-----:|
| **NestPay** | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Garanti** | :white_check_mark: | :white_check_mark: | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **PosNet** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **PayFor** | :white_check_mark: | :white_check_mark: | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Vakıfbank** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Kuveyt Türk** | | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **iyzico** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Moka** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Sipay** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Param** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **PayTR** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Tosla** | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **EsnekPOS** | | :white_check_mark: | | :white_check_mark: | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Paratika** | :white_check_mark: | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Lidio** | :white_check_mark: | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| **Craftgate** | :white_check_mark: | :white_check_mark: | | | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |

> NestPay driver'ı tek başına 8+ bankayı destekler. Toplam **20+ banka ve ödeme kuruluşu** tek API ile kullanılabilir.

## Gereksinimler

- PHP 8.2+
- PSR-18 HTTP Client (Guzzle, Symfony HttpClient, vb.)
- Laravel 11/12 (opsiyonel — ServiceProvider, Facade, config, routes)

## Kurulum

```bash
composer require lbali/truepos
```

### Laravel

Config dosyasini yayinlayin:

```bash
php artisan vendor:publish --tag=truepos-config
```

İşlem loglaması istiyorsanız migration'ları da yayınlayın:

```bash
php artisan vendor:publish --tag=truepos-migrations
php artisan migrate
```

## Kullanım

### Basit Ödeme

```php
use TruePos\Facades\TruePos;
use TruePos\Builder\PaymentRequestBuilder;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Customer;

$request = PaymentRequestBuilder::create()
    ->card(new CreditCard(
        number: '4546711234567894',
        expiryMonth: '12',
        expiryYear: '30',
        cvv: '000',
        holderName: 'Ali Yilmaz',
    ))
    ->amount(250.50)
    ->installment(3)
    ->customer(new Customer(ip: request()->ip()))
    ->build();

$response = TruePos::gateway('akbank')->purchase($request);

if ($response->isSuccessful()) {
    // $response->transactionId
    // $response->authCode
}
```

### 3D Secure Ödeme

```php
$request = PaymentRequestBuilder::create()
    ->card($card)
    ->amount(500.00)
    ->threeD(route('truepos.threed.callback'))
    ->installment(6)
    ->customer(new Customer(ip: request()->ip()))
    ->build();

$response = TruePos::gateway('garanti')->purchase($request);

if ($response->isThreeDRedirect()) {
    return view('truepos::redirect', [
        'gatewayUrl'  => $response->threeDSecureData->gatewayUrl,
        'parameters'  => $response->threeDSecureData->formParameters,
    ]);
}
```

3D Secure callback'i Laravel'de otomatik yonetilir. Sonucu dinlemek icin event listener kullanin:

```php
use TruePos\Events\PaymentCompleted;
use TruePos\Events\PaymentFailed;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $response = $event->response;
    // Siparisi onayla
});

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $response = $event->response;
    // Hata yonetimi
});
```

### Framework-Agnostic (Symfony, vanilla PHP, vb.)

Laravel olmadan da kullanilabilir. Sadece PSR-18 HTTP client ve opsiyonel PSR-16 cache gerekir:

```php
use GuzzleHttp\Client;
use TruePos\Builder\PaymentRequestBuilder;
use TruePos\Factory\GatewayFactory;
use TruePos\TruePosManager;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Customer;

$manager = new TruePosManager(
    config: [
        'default' => 'akbank',
        'gateways' => [
            'akbank' => [
                'driver' => 'nestpay',
                'client_id' => 'your_client_id',
                'username' => 'your_username',
                'password' => 'your_password',
                'store_key' => 'your_store_key',
                'store_type' => '3d',
                'payment_url' => 'https://www.sanalakpos.com/fim/api',
                'threed_gateway_url' => 'https://www.sanalakpos.com/fim/est3Dgate',
            ],
        ],
    ],
    factory: new GatewayFactory(),
    httpClient: new Client(['timeout' => 30]),
);

$request = PaymentRequestBuilder::create()
    ->card(new CreditCard('4546711234567894', '12', '30', '000'))
    ->amount(100.00)
    ->customer(new Customer(ip: '127.0.0.1'))
    ->build();

$response = $manager->gateway('akbank')->purchase($request);
```

### Gateway Degistirme

Ayni kod, farkli banka:

```php
TruePos::gateway('akbank')->purchase($request);
TruePos::gateway('isbank')->purchase($request);
TruePos::gateway('garanti')->purchase($request);
TruePos::gateway('yapikredi')->purchase($request);
TruePos::gateway('iyzico')->purchase($request);
TruePos::gateway('tosla')->purchase($request);

// Veya .env'den default gateway kullan:
TruePos::gateway()->purchase($request);
```

### Iade

```php
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\ValueObjects\Money;

$response = TruePos::gateway('akbank')->refund(new RefundRequest(
    orderId: 'ORD-12345',
    amount: Money::fromDecimal(50.00), // Kismi iade
));
```

### Iptal

```php
use TruePos\DataTransferObjects\CancelRequest;

$response = TruePos::gateway('akbank')->cancel(new CancelRequest(
    orderId: 'ORD-12345',
));
```

### On Provizyon + Kapama

```php
// Tutari bloke et
$request = PaymentRequestBuilder::create()
    ->card($card)
    ->amount(1000.00)
    ->preAuth()
    ->build();

$holdResponse = TruePos::gateway()->preAuthorize($request);

// Daha sonra: kapat (daha dusuk tutarla da olabilir)
$captureResponse = TruePos::gateway()->postAuthorize(
    transactionId: $holdResponse->transactionId,
    amount: Money::fromDecimal(800.00),
);
```

## Konfigürasyon

`.env` dosyaniza banka bilgilerinizi ekleyin:

```env
TRUEPOS_GATEWAY=akbank

# Akbank (NestPay)
AKBANK_CLIENT_ID=your_client_id
AKBANK_USERNAME=your_username
AKBANK_PASSWORD=your_password
AKBANK_STORE_KEY=your_store_key

# Garanti
GARANTI_TERMINAL_ID=your_terminal_id
GARANTI_MERCHANT_ID=your_merchant_id
GARANTI_PROV_PASSWORD=your_password
GARANTI_STORE_KEY=your_store_key

# Yapi Kredi (PosNet)
YAPIKREDI_MERCHANT_ID=your_merchant_id
YAPIKREDI_TERMINAL_ID=your_terminal_id
YAPIKREDI_POSNET_ID=your_posnet_id
YAPIKREDI_ENC_KEY=your_enc_key

# iyzico
IYZICO_API_KEY=your_api_key
IYZICO_SECRET_KEY=your_secret_key

# Tosla
TOSLA_CLIENT_ID=your_client_id
TOSLA_API_USER=your_api_user
TOSLA_API_PASS=your_api_pass
```

Tüm gateway konfigürasyonları için `config/truepos.php` dosyasini inceleyin.

## Mimari

Bu paket asagidaki OOP design pattern'lerini kullanir:

| Pattern | Kullanim Yeri |
|---------|--------------|
| **Template Method** | `AbstractGateway` — odeme akisinin iskeleti |
| **Strategy** | `HashGenerator`, `Serializer`, `ResponseParser` |
| **Factory Method** | `GatewayFactory` — config'den gateway yaratma |
| **Builder** | `PaymentRequestBuilder` — fluent, immutable |
| **Adapter** | `ResponseParser` — banka cevaplarini normalize etme |
| **Decorator** | `LoggingGateway`, `RetryGateway` |
| **Observer** | PSR-14 Events (Laravel/Symfony uyumlu) |
| **State** | `TransactionStateMachine` |
| **Chain of Responsibility** | `ValidationPipeline` |
| **Value Object** | `CreditCard`, `Money`, `Customer` |
| **Null Object** | `NullTransactionRepository` |

## Test

```bash
composer test        # PHPUnit
composer analyse     # PHPStan level 8
composer lint        # Laravel Pint
composer check       # Hepsi birden
```

## Bilinen Kisitlamalar

- Her gateway icin gercek banka/acquirer ortami farklilik gosterebilir. Production'a almadan once kendi banka credential'larinizla test edin.
- PCI-DSS uyumlulugu merchant/uygulama tarafinin sorumlulugundadir. Bu paket kart verisini bellekte isler, saklamaz.
- Kart verisini mumkunse 3D Host veya hosted payment form ile dogrudan bankada toplamak onerilir (`->threeDHost()` kullanin).
- Banka API'leri zaman zaman breaking change yapabilir. Sorun yasarsaniz issue acin.

## Guvenlik

Guvenlik acigi bildirimi icin **public issue acmayin**. Detaylar icin [SECURITY.md](SECURITY.md) dosyasina bakin.

## Katkida Bulunma

Detaylar icin [CONTRIBUTING.md](CONTRIBUTING.md) dosyasina bakin.

Kisa ozet — yeni gateway eklemek icin:

1. `src/Gateways/YeniGateway/` dizininde 3 dosya olusturun:
   - `YeniGatewayGateway.php` (extends `AbstractGateway`)
   - `YeniGatewayHashGenerator.php` (implements `HashGeneratorInterface`)
   - `YeniGatewayResponseParser.php` (implements `ResponseParserInterface`)

2. `src/Enums/Gateway.php`'ye yeni case ekleyin

3. `src/Factory/GatewayFactory.php`'de register edin

4. `config/truepos.php`'ye ornek config ekleyin

5. `tests/` altina testleri ve fixture'lari yazin

## Lisans

MIT
