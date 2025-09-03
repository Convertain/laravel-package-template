# Laravel Package Template

This is a scaffold template for creating Convertain Laravel packages with standardized structure, testing, and CI/CD.

## Features

- 🚀 Laravel 12.x & PHP 8.4+ support
- 🔑 UUID/ULID public identifiers with integer primary keys
- 🧩 Runtime package detection and integration
- 🎨 FluxUI component integration
- ✅ 100% test coverage requirement
- 🔍 PHPStan Level 10 static analysis
- 🎯 Laravel Pint code formatting (PSR-12)
- 🔄 GitHub Actions CI/CD pipelines
- 📦 Automatic Packagist releases

## Installation

```bash
composer require convertain/package-name
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=package-template-config
```

## Usage

### HasPublicId Trait

Add UUID support to your models while keeping integer primary keys:

```php
use Convertain\PackageTemplate\Traits\HasPublicId;

class YourModel extends Model
{
    use HasPublicId;
    
    // Model will automatically get a UUID on creation
    // Routes will use UUID instead of ID
}
```

### Package Integration Detection

The package automatically detects and integrates with other Convertain packages:

- **Organizations**: Adds organization-scoped features
- **Permissions**: Registers package-specific permissions
- **Checkout**: Integrates billing features

## Development

### Setup

```bash
composer install
```

### Testing

Run tests with coverage:

```bash
composer test
```

Run tests with HTML coverage report:

```bash
composer test-coverage
```

### Code Quality

Run Laravel Pint:

```bash
composer lint
```

Run PHPStan:

```bash
vendor/bin/phpstan analyse
```

### Development Server

Start the Testbench development server:

```bash
composer serve
```

## Structure

```
├── config/                 # Configuration files
├── database/
│   ├── factories/         # Model factories
│   └── migrations/        # Database migrations
├── resources/
│   ├── lang/             # Translation files
│   └── views/            # Blade views
├── routes/               # Route files
│   ├── api.php
│   └── web.php
├── src/                  # Source code
│   ├── Traits/          # Reusable traits
│   └── PackageTemplateServiceProvider.php
├── tests/               # Test files
│   ├── Feature/
│   ├── Pest/
│   ├── Unit/
│   ├── Pest.php
│   └── TestCase.php
├── .github/
│   └── workflows/       # GitHub Actions
├── composer.json
├── phpstan.neon        # PHPStan config
└── pint.json          # Laravel Pint config
```

## Creating a New Package

1. Copy this template
2. Replace `PackageTemplate` with your package name
3. Replace `package-template` with your package slug
4. Update composer.json with your package details
5. Update configuration and service provider
6. Add your package-specific logic

## Quality Standards

- ✅ 100% test coverage (enforced)
- ✅ PHPStan Level 10 (no errors)
- ✅ Laravel Pint PSR-12 formatting
- ✅ Strict types declaration
- ✅ Full PHPDoc documentation

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
