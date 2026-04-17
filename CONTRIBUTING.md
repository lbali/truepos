# Contributing to TruePos

## Adding a New Gateway

1. Create 3 files in `src/Gateways/YourGateway/`:
   - `YourGatewayGateway.php` (extends `AbstractGateway`)
   - `YourGatewayHashGenerator.php` (implements `HashGeneratorInterface`)
   - `YourGatewayResponseParser.php` (implements `ResponseParserInterface`)

2. Add a new case to `src/Enums/Gateway.php`

3. Register in `src/Factory/GatewayFactory.php` via `ensureDefaultsRegistered()`

4. Add config example to `config/truepos.php`

5. Add tests and fixtures under `tests/`

## Running Tests

```bash
composer test      # PHPUnit
composer analyse   # PHPStan
composer lint      # Laravel Pint
composer check     # All of the above
```

## Code Style

This project uses Laravel Pint. Run `composer lint` before submitting.

## Pull Requests

- One feature per PR
- Include tests
- Follow existing patterns
