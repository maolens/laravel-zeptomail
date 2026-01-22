# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-01-22

### Added
- Full support for all ZeptoMail regions (US, EU, IN, AU, JP, CA, SA, CN)
- Custom endpoint support for self-hosted or custom ZeptoMail instances
- Comprehensive custom exception classes for better error handling:
  - `ConfigurationException` for configuration errors
  - `ConnectionException` for network connectivity issues
  - `ApiException` for API response errors with HTTP status and response body
- Optional logging system with configurable verbosity
- Support for Reply-To headers
- Support for text-only emails (not just HTML)
- Configurable request timeout
- Type-safe configuration with PHP 8.0+ type hints
- Detailed PHPDoc comments throughout the codebase
- Environment variable for `ZEPTOMAIL_API_KEY` (in addition to `ZEPTOMAIL_TOKEN`)
- Region-based endpoint resolution
- Proper HTTP error handling with detailed error messages
- Configuration publishing via `php artisan vendor:publish`

### Changed
- **BREAKING**: Constructor signature for `ZeptoMailTransport` now accepts multiple parameters instead of just API key and host
- **BREAKING**: Environment variable `ZEPTOMAIL_HOST` replaced with `ZEPTOMAIL_REGION`
- Improved payload building with cleaner, more maintainable code
- Enhanced attachment handling with better MIME type detection
- Refactored recipient handling for better type safety
- Updated HTTP client configuration with sensible defaults
- Modernized code style to follow Laravel best practices
- Updated User-Agent header to identify package version

### Fixed
- Fixed incorrect endpoint construction for non-US regions
- Fixed potential null pointer issues in address formatting
- Fixed missing error context in exception logging
- Fixed inconsistent handling of empty recipient lists
- Improved validation of API keys
- Fixed potential JSON parsing errors in API responses

### Removed
- Removed deprecated `Swift_Events_EventListener` reference
- Removed hardcoded domain mapping from transport class (moved to config)
- Removed unused `registerPlugin` method
- Removed unnecessary dependencies

### Security
- Added API key validation to prevent empty or malformed keys
- Improved error message sanitization to prevent information leakage
- Added request timeout to prevent hanging connections
- Enhanced exception handling to avoid exposing sensitive data in logs

## [1.0.0] - Previous Release

Initial release with basic ZeptoMail support for Laravel.

### Features
- Basic email sending via ZeptoMail API
- Support for HTML emails
- Attachment support
- CC and BCC support
- Basic error handling
