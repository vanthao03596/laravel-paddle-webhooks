# Handle Paddle webhooks in a Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vanthao03596/laravel-paddle-webhooks.svg?style=flat-square)](https://packagist.org/packages/vanthao03596/laravel-paddle-webhooks)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vanthao03596/laravel-paddle-webhooks/run-tests?label=tests)](https://github.com/vanthao03596/laravel-paddle-webhooks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vanthao03596/laravel-paddle-webhooks/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vanthao03596/laravel-paddle-webhooks/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vanthao03596/laravel-paddle-webhooks.svg?style=flat-square)](https://packagist.org/packages/vanthao03596/laravel-paddle-webhooks)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require vanthao03596/laravel-paddle-webhooks
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Vanthao03596\PaddleWebhooks\PaddleWebhooksServiceProvider" --tag="laravel-paddle-webhooks-migrations"
php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Vanthao03596\PaddleWebhooks\PaddleWebhooksServiceProvider" --tag="laravel-paddle-webhooks-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$laravel-paddle-webhooks = new Vanthao03596\PaddleWebhooks();
echo $laravel-paddle-webhooks->echoPhrase('Hello, Spatie!');
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

- [phamthao](https://github.com/vanthao03596)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
