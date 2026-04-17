# TruePos

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lbali/truepos.svg?style=flat-square)](https://packagist.org/packages/lbali/truepos)
[![License](https://img.shields.io/github/license/lbali/truepos?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/lbali/truepos?style=flat-square)](composer.json)

Tüm Türk sanal POS sağlayıcılarını tek bir birleşik API altında toplayan, framework-bağımsız PHP paketi. Laravel entegrasyonu dahildir.

Bir sağlayıcıdan diğerine geçmek için tek yapmanız gereken ayarları değiştirmek — kodunuz aynı kalır.

> **Durum: Beta** — Canlıya almadan önce kendi banka/ödeme kuruluşu bilgilerinizle test edin. 3DS geri dönüşleri mümkün olan yerlerde hash ile doğrulanır; bazı sağlayıcılarda (PosNet, Iyzico) doğrulama sunucudan sunucuya provizyon adımında yapılır.

## Desteklenen Sağlayıcılar

### Banka Sanal POS'ları

| Sağlayıcı | Sürücü | Ayar Adı | Bankalar |
|-----------|--------|----------|----------|
| **NestPay (EST)** | `nestpay` | `akbank` `isbank` `ziraat` `halkbank` `teb` `denizbank` | Akbank, İş Bankası, Ziraat, Halkbank, TEB, Denizbank, Anadolubank, ING |
| **Garanti GVP** | `garanti` | `garanti` | Garanti BBVA |
| **PosNet** | `posnet` | `yapikredi` | Yapı Kredi |
| **PayFor** | `payfor` | `qnbfinansbank` | QNB Finansbank |
| **Vakıfbank VPOS** | `vakifbank` | `vakifbank` | Vakıfbank |
| **Kuveyt Türk** | `kuveytturk` | `kuveytturk` | Kuveyt Türk |

### Ödeme Kuruluşları

| Sağlayıcı | Sürücü | Ayar Adı |
|-----------|--------|----------|
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

| Sağlayıcı | Sürücü | Ayar Adı |
|-----------|--------|----------|
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

> NestPay sürücüsü tek başına 8+ bankayı destekler. Toplamda **20+ banka ve ödeme kuruluşu** tek API ile kullanılabilir.

## Gereksinimler

- PHP 8.2+
- PSR-18 HTTP İstemcisi (Guzzle, Symfony HttpClient vb.)
- Laravel 11/12 (isteğe bağlı — ServiceProvider, Facade, ayar dosyası, rotalar)

## Kurulum

```bash
composer require lbali/truepos
```

### Laravel Kurulumu

Ayar dosyasını yayınlayın:

```bash
php artisan vendor:publish --tag=truepos-config
```

İşlem kaydı tutmak istiyorsanız migration'ları da yayınlayın:

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
        holderName: 'Ali Yılmaz',
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

3D Secure geri dönüşü Laravel'de otomatik yönetilir. Sonucu dinlemek için olay dinleyicileri kullanın:

```php
use TruePos\Events\PaymentCompleted;
use TruePos\Events\PaymentFailed;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $response = $event->response;
    // Siparişi onayla
});

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $response = $event->response;
    // Hata yönetimi
});
```

### Framework-Bağımsız Kullanım (Symfony, saf PHP vb.)

Laravel olmadan da kullanılabilir. Sadece PSR-18 HTTP istemcisi ve isteğe bağlı PSR-16 önbellek gerekir:

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

### Sağlayıcı Değiştirme

Aynı kod, farklı banka:

```php
TruePos::gateway('akbank')->purchase($request);
TruePos::gateway('isbank')->purchase($request);
TruePos::gateway('garanti')->purchase($request);
TruePos::gateway('yapikredi')->purchase($request);
TruePos::gateway('iyzico')->purchase($request);
TruePos::gateway('tosla')->purchase($request);

// Veya .env'den varsayılan sağlayıcıyı kullan:
TruePos::gateway()->purchase($request);
```

### İade

```php
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\ValueObjects\Money;

$response = TruePos::gateway('akbank')->refund(new RefundRequest(
    orderId: 'ORD-12345',
    amount: Money::fromDecimal(50.00), // Kısmi iade
));
```

### İptal

```php
use TruePos\DataTransferObjects\CancelRequest;

$response = TruePos::gateway('akbank')->cancel(new CancelRequest(
    orderId: 'ORD-12345',
));
```

### Ön Provizyon + Kapama

```php
// Tutarı bloke et
$request = PaymentRequestBuilder::create()
    ->card($card)
    ->amount(1000.00)
    ->preAuth()
    ->build();

$holdResponse = TruePos::gateway()->preAuthorize($request);

// Daha sonra: kapat (daha düşük tutarla da olabilir)
$captureResponse = TruePos::gateway()->postAuthorize(
    transactionId: $holdResponse->transactionId,
    amount: Money::fromDecimal(800.00),
);
```

## Yapılandırma

`.env` dosyanıza banka bilgilerinizi ekleyin:

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

# Yapı Kredi (PosNet)
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

Tüm sağlayıcı yapılandırmaları için `config/truepos.php` dosyasını inceleyin.

## Mimari

Bu paket aşağıdaki OOP tasarım kalıplarını kullanır:

| Kalıp | Kullanım Yeri |
|-------|--------------|
| **Şablon Yöntem** | `AbstractGateway` — ödeme akışının iskeleti |
| **Strateji** | `HashGenerator`, `Serializer`, `ResponseParser` |
| **Fabrika Yöntemi** | `GatewayFactory` — yapılandırmadan sağlayıcı oluşturma |
| **İnşaatçı** | `PaymentRequestBuilder` — akıcı, değişmez |
| **Uyarlayıcı** | `ResponseParser` — banka yanıtlarını normalleştirme |
| **Dekoratör** | `LoggingGateway`, `RetryGateway` |
| **Gözlemci** | PSR-14 olayları (Laravel/Symfony uyumlu) |
| **Durum** | `TransactionStateMachine` |
| **Sorumluluk Zinciri** | `ValidationPipeline` |
| **Değer Nesnesi** | `CreditCard`, `Money`, `Customer` |
| **Boş Nesne** | `NullTransactionRepository` |

## Test

```bash
composer test        # PHPUnit
composer analyse     # PHPStan seviye 8
composer lint        # Laravel Pint
composer check       # Hepsi birden
```

## Bilinen Kısıtlamalar

- Her sağlayıcı için gerçek banka/ödeme kuruluşu ortamı farklılık gösterebilir. Canlıya almadan önce kendi banka bilgilerinizle test edin.
- PCI-DSS uyumluluğu üye işyeri/uygulama tarafının sorumluluğundadır. Bu paket kart verisini bellekte işler, saklamaz.
- Kart verisini mümkünse 3D Host veya barındırılan ödeme formu ile doğrudan bankada toplamak önerilir (`->threeDHost()` kullanın).
- Banka API'leri zaman zaman geriye uyumsuz değişiklik yapabilir. Sorun yaşarsanız issue açın.

## Güvenlik

Güvenlik açığı bildirimi için **public issue açmayın**. Ayrıntılar için [SECURITY.md](SECURITY.md) dosyasına bakın.

## Katkıda Bulunma

Ayrıntılar için [CONTRIBUTING.md](CONTRIBUTING.md) dosyasına bakın.

Kısa özet — yeni sağlayıcı eklemek için:

1. `src/Gateways/YeniSaglayici/` dizininde 3 dosya oluşturun:
   - `YeniSaglayiciGateway.php` (extends `AbstractGateway`)
   - `YeniSaglayiciHashGenerator.php` (implements `HashGeneratorInterface`)
   - `YeniSaglayiciResponseParser.php` (implements `ResponseParserInterface`)

2. `src/Enums/Gateway.php` dosyasına yeni case ekleyin

3. `src/Factory/GatewayFactory.php` dosyasında kayıt edin

4. `config/truepos.php` dosyasına örnek yapılandırma ekleyin

5. `tests/` altına testleri ve fixture'ları yazın

## Lisans

MIT
