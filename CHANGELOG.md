# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- 16 payment gateway integrations
- Framework-agnostic core with optional Laravel integration
- 3D Secure callback handling with auto gateway mapping
- Fluent PaymentRequestBuilder
- Transaction state machine
- Logging and Retry decorators
- Validation pipeline
- Comprehensive test suite (73 tests)

### Security
- Fail-closed 3DS callback verification
- CreditCard __debugInfo masking
- Sensitive data redaction for events and database
- Non-idempotent operation retry protection
