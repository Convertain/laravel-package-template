# Laravel Package Template

[![Laravel 12.x](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP 8.2 - 8.5](https://img.shields.io/badge/PHP-8.2%20to%208.5-777BB4?style=flat-square&logo=php)](https://www.php.net)
[![PHPStan Level 10](https://img.shields.io/badge/PHPStan-Level%2010-brightgreen?style=flat-square)](https://phpstan.org)
[![Laravel Pint PSR-12](https://img.shields.io/badge/Laravel%20Pint-PSR--12-FF2D20?style=flat-square)](https://laravel.com/docs/pint)
[![Sponsor Convertain](https://img.shields.io/badge/Sponsor-Convertain-blue?style=flat-square&logo=github)](https://github.com/sponsors/Convertain)

A modern, fully-featured Laravel package template that scaffolds a production-ready package with interactive configuration, integrated tooling, CI/CD pipelines, and Laravel Boost support.

## Features

- **Interactive Installer**: Guided setup with choices for vendor name, package name, license, and optional features
- **Modern Tooling**: [PHPStan](https://phpstan.org) Level 10, [Laravel Pint](https://laravel.com/docs/pint) PSR-12, [PHPUnit](https://phpunit.de) with [Orchestra Testbench](https://github.com/orchestraplatform/testbench)
- **MCP Configuration**: Automatic setup for VS Code, Cursor, Gemini, and Junie with [Laravel Boost](https://boost.laravel.com) MCP server
- **Resource Management**: Selective inclusion of migrations, views, translations, routes, and publishable assets
- **CI/CD Pipeline**: GitHub Actions workflow with linting, static analysis, and tests
- **Laravel Boost Integration**: Full support for [Laravel Boost](https://boost.laravel.com) with testbench MCP configuration
- **Workbench Support**: Local development environment for testing your package

## Quick Start

1. **Clone the template**:

   ```bash
   git clone git@github.com:Convertain/laravel-package-template.git your-package-name
   cd your-package-name
   ```

2. **Run the installer**:

   ```bash
   php install.php
   ```

   This will guide you through:
   - Package name and vendor details
   - License selection (MIT, Proprietary, Apache 2.0, or BSD-3-Clause)
   - Optional feature selection (migrations, views, translations, routes, publishable assets)
   - Composer dependency installation
   - Workbench setup
   - Database migrations
   - Laravel Boost installation
   - MCP configuration updates
   - Code quality checks (lint and static analysis)

3. **Start developing**:

   ```bash
   composer serve  # Start the workbench app
   ```

## Available Commands

### Testing & Quality Assurance

```bash
composer test      # Run the test suite with PHPUnit
composer analyse   # Run PHPStan static analysis (Level 10)
composer lint      # Check and fix code style with Laravel Pint
composer lint:fix  # Auto-fix code style issues
```

### Development

```bash
composer serve     # Start the Workbench development server
composer workbench:install  # Reinstall the workbench
```

## Package Configuration

After installation, your package will be:

- Named as `vendor_slug/package_slug`
- Using namespace `Vendor\Package`
- Auto-discovered by Laravel via `Vendor\Package\PackageServiceProvider`

### Publishing Package Assets

Users of your package can publish configuration and assets:

```bash
php artisan vendor:publish --tag=your-package-config
php artisan vendor:publish --tag=your-package-migrations
```

## Included Technologies

| Technology | Version | Purpose | Documentation |
|-----------|---------|---------|---------|
| [Laravel](https://laravel.com) | 12.x | Framework foundation | [Docs](https://laravel.com/docs) |
| [PHP](https://www.php.net) | 8.2 - 8.5 | Language requirement | [Docs](https://www.php.net/docs.php) |
| [Orchestra Testbench](https://github.com/orchestraplatform/testbench) | ^10 | Laravel package testing | [Docs](https://github.com/orchestraplatform/testbench) |
| [PHPUnit](https://phpunit.de) | ^11 | Unit testing framework | [Docs](https://docs.phpunit.de) |
| [PHPStan](https://phpstan.org) | ^2 (Level 10) | Static code analysis | [Docs](https://phpstan.org/user-guide/getting-started) |
| [Larastan](https://github.com/larastan/larastan) | ^3 | Laravel-aware PHPStan | [Docs](https://github.com/larastan/larastan) |
| [Laravel Pint](https://laravel.com/docs/pint) | ^1.14 | Code style formatter | [Docs](https://laravel.com/docs/pint) |
| [Laravel Boost](https://boost.laravel.com) | ^1.0 | Development enhancement | [Docs](https://boost.laravel.com) |
| [phpstan/extension-installer](https://github.com/phpstan/extension-installer) | ^1.4 | PHPStan extension auto-discovery | [GitHub](https://github.com/phpstan/extension-installer) |

## MCP Configuration

The installer automatically updates MCP configurations for popular code editors:

- **VS Code**: `.vscode/mcp.json`
- **Cursor**: `.cursor/mcp.json`
- **Gemini**: `.gemini/settings.json`
- **Junie**: `.junie/mcp/mcp.json`
- **Generic**: `.mcp.json`

All configurations are set to use `vendor/bin/testbench boost:mcp` for Laravel Boost integration. If you're using a different editor or the configuration isn't auto-updated, manually change:

```json
{
    "laravel-boost": {
        "command": "vendor/bin/testbench",
        "args": ["boost:mcp"]
    }
}
```

## Directory Structure

```text
├── config/                      # Package configuration files
├── data/                        # Template resources (removed after install)
├── lang/                        # Translation files
├── src/
│   ├── Contracts/               # Package interfaces
│   ├── Services/                # Core service classes
│   ├── Steps/                   # Installation steps
│   ├── Traits/                  # Reusable traits
│   └── PackageServiceProvider.php
├── tests/
│   ├── Feature/                 # Feature tests
│   ├── Unit/                    # Unit tests
│   └── TestCase.php
├── workbench/                   # Development/testing app (created by installer)
├── composer.json
├── phpstan.neon.dist
├── phpunit.xml.dist
├── pint.json
└── README.md
```

## CI/CD Pipeline

The repository includes a GitHub Actions workflow that:

1. Validates code style with Laravel Pint
2. Performs static analysis with PHPStan Level 10
3. Runs the test suite with PHPUnit
4. Runs on pull requests and pushes to main branches

Branches: `master`, `1.x`

## License

This template defaults to the MIT License. You can choose a different license during installation:

- MIT
- Proprietary
- Apache License 2.0
- BSD 3-Clause License

See [LICENSE.md](LICENSE.md) for the selected license details.

## Support

For issues or questions about the template, please open an issue on this repository.

---

Made with ❤️ by [Convertain](https://github.com/Convertain)

