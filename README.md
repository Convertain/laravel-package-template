# Laravel Package Template

This repository is a starter template for building Laravel packages. Run the configurator to replace placeholders and bootstrap a new package quickly.

## Quick start

1. Clone the template: `git clone git@github.com:Convertain/laravel-package-template.git your-package-name`
2. Install it: `php install.php` (installs dependencies, sets up workbench, runs migrations, and installs [Laravel Boost](https://boost.laravel.com/))
3. Run checks:
   - Tests: `composer test`
   - Static analysis: `composer analyse`
   - Code style: `composer lint`
   - Workbench app: `composer serve` (available after install)

## After configuration

- The package name will be set to `:vendor_slug/:package_slug` with namespace `Vendor\Package`.
- The service provider `Vendor\Package\PackageServiceProvider` is auto-discovered by Laravel.
- Publish the config file when needed: `php artisan vendor:publish --tag=package-template-config`.

## Included tooling

- PHPUnit with Orchestra Testbench for Laravel package testing.
- PHPStan (level 10) with Larastan for framework-aware analysis.
- Laravel Pint with a PSR-12 preset.
- Workbench for running the package inside a local Laravel app.

## Scripts

- `composer test` — run the test suite.
- `composer analyse` — run PHPStan.
- `composer lint` — run Pint.

## Laravel Boost + Testbench

- If you use Laravel Boost in VS Code, the MCP configuration may default to running the server via `php artisan boost:mcp`. For packages running under Orchestra Testbench, you may need to change this to use Testbench instead:
   - From: `php artisan boost:mcp`
   - To: `vendor/bin/testbench boost:mcp`

- The installer attempts to update `.vscode/mcp.json` automatically for the common schemas. If the file does not exist (non–VS Code editors) or uses a different schema, adjust it manually as shown above.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
