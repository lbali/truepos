# TruePos'a Katkıda Bulunma

## Yeni Sağlayıcı Ekleme

1. `src/Gateways/YeniSaglayici/` dizininde 3 dosya oluşturun:
   - `YeniSaglayiciGateway.php` (extends `AbstractGateway`)
   - `YeniSaglayiciHashGenerator.php` (implements `HashGeneratorInterface`)
   - `YeniSaglayiciResponseParser.php` (implements `ResponseParserInterface`)

2. `src/Enums/Gateway.php` dosyasına yeni case ekleyin

3. `src/Factory/GatewayFactory.php` içinde `ensureDefaultsRegistered()` ile kayıt edin

4. `config/truepos.php` dosyasına örnek yapılandırma ekleyin

5. `tests/` altına testleri ve fixture'ları yazın

## Testleri Çalıştırma

```bash
composer test      # PHPUnit
composer analyse   # PHPStan seviye 8
composer lint      # Laravel Pint
composer check     # Hepsi birden
```

## Kod Stili

Bu proje Laravel Pint kullanır. Göndermeden önce `composer lint` çalıştırın.

## Pull Request Kuralları

- Her PR tek bir özellik veya düzeltme içermeli
- Testler dahil edilmeli
- Mevcut kalıplara uygun olunmalı
