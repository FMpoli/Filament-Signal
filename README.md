# Filament Signal is an automation hub for Filament 4 that listens to any Laravel event and reacts with configurable actions. Manage email templates via the built‑in Tiptap editor, fire webhooks, send multi-channel notifications, and track delivery logs—all from a single panel-ready plugin.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/base33/filament-signal.svg?style=flat-square)](https://packagist.org/packages/base33/filament-signal)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/base33/filament-signal/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/base33/filament-signal/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/base33/filament-signal/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/base33/filament-signal/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/base33/filament-signal.svg?style=flat-square)](https://packagist.org/packages/base33/filament-signal)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require base33/filament-signal
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-signal-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-signal-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-signal-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentSignal = new Base33\FilamentSignal();
echo $filamentSignal->echoPhrase('Hello, Base33!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Francesco Mulassano](https://github.com/FMPoli)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
