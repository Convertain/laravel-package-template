# :package_slug

[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/:vendor_slug/:package_slug/ci.yml?branch=master&label=tests&style=flat-square)](https://github.com/:vendor_slug/:package_slug/actions)
[![Packagist Version](https://img.shields.io/packagist/v/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)
[![Total Downloads](https://img.shields.io/packagist/dt/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)
[![PHP Version](https://img.shields.io/packagist/php-v/:vendor_slug/:package_slug.svg?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/github/license/:vendor_slug/:package_slug.svg?style=flat-square)](LICENSE.md)

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
