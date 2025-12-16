# :package_slug

[![Tests](https://github.com/:vendor_slug/:package_slug/actions/workflows/ci.yml/badge.svg)](https://github.com/:vendor_slug/:package_slug/actions/workflows/ci.yml)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)](https://www.php.net)
[![PHPStan Level :phpstan_level](https://img.shields.io/badge/PHPStan-Level%20:phpstan_level-4F5B93?style=flat)](https://phpstan.org)
[![Laravel Pint](https://img.shields.io/badge/Laravel%20Pint-:pint_preset_display-F05340?style=flat)](https://laravel.com/docs/pint)
[![License](https://img.shields.io/badge/License-:license-red.svg)](LICENSE.md)

:package_description

## Installation

Install the package via Composer:

```bash
composer require :vendor_slug/:package_slug
```

<!-- IF:config -->
## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag=:package_slug-config
```

The config file will be published to `config/:package_slug.php`.
<!-- ENDIF:config -->

<!-- IF:routes -->
## Routes

This package registers routes automatically:

<!-- IF:routes_web -->
- Web routes from `routes/web.php`
<!-- ENDIF:routes_web -->
<!-- IF:routes_api -->
- API routes from `routes/api.php`
<!-- ENDIF:routes_api -->
<!-- ENDIF:routes -->

<!-- IF:views -->
## Views

Views are loaded from the package and can be published for customization:

```bash
php artisan vendor:publish --tag=:package_slug-views
```

Published to: `resources/views/vendor/:package_slug`.
<!-- ENDIF:views -->

<!-- IF:translations -->
## Translations

Translations are loaded from the package `lang` directory and can be published:

```bash
php artisan vendor:publish --tag=:package_slug-lang
```

Published to: `lang/vendor/:package_slug`.
<!-- ENDIF:translations -->

<!-- IF:migrations -->
## Migrations

Migrations are loaded automatically from the package. You can optionally publish them:

```bash
php artisan vendor:publish --tag=:package_slug-migrations
```

Published to: `database/migrations`.
<!-- ENDIF:migrations -->

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis and code style checks:

```bash
composer analyse
composer lint
```

## License

:license © :year :author_name
