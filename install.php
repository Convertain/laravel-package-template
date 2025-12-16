#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('This configurator must be run from the CLI.'.PHP_EOL);
}

function ask(string $question, string $default = ''): string
{
    $suffix = $default !== '' ? " [{$default}]" : '';
    $answer = readline($question.$suffix.': ');

    return $answer !== '' ? trim($answer) : $default;
}

function confirm(string $question, bool $default = false): bool
{
    $defaultPrompt = $default ? 'Y/n' : 'y/N';
    $answer = strtolower(ask($question." ({$defaultPrompt})", $default ? 'y' : 'n'));

    return in_array($answer, ['y', 'yes'], true);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

    return trim($value, '-');
}

function studly(string $value): string
{
    $value = str_replace(['-', '_'], ' ', $value);
    $value = ucwords($value);

    return str_replace(' ', '', $value);
}

function runCommand(string $command, string $errorMessage): void
{
    echo "Running: {$command}".PHP_EOL;
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        exit($errorMessage.PHP_EOL);
    }
}

/**
 * Run a command silently, capturing output. Returns true on success, exits on failure.
 */
function runCommandSilent(string $command, string $errorMessage): bool
{
    $output = [];
    $exitCode = 0;
    exec($command.' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        // Show the error output before exiting
        echo "\n".implode("\n", $output)."\n";
        exit($errorMessage.PHP_EOL);
    }

    return true;
}

function replaceInFiles(array $files, array $replacements): void
{
    foreach ($files as $file) {
        if (! file_exists($file)) {
            continue;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            echo "Could not read {$file}".PHP_EOL;
            continue;
        }

        $updated = str_replace(array_keys($replacements), array_values($replacements), $contents);
        file_put_contents($file, $updated);
    }
}

// Install Laravel Prompts for nice UI
$dataDir = __DIR__.'/data';
$dataVendorDir = $dataDir.'/vendor';

if (! is_dir($dataVendorDir)) {
    echo "Installing installer dependencies...\n";
    runCommand('cd '.escapeshellarg($dataDir).' && composer install --no-dev --no-progress --quiet', 'Failed to install installer dependencies.');
}

// Load Laravel Prompts
if (file_exists($dataVendorDir.'/autoload.php')) {
    require $dataVendorDir.'/autoload.php';
}

// Pre-fetch git defaults
$gitName = trim((string) shell_exec('git config user.name'));
$gitEmail = trim((string) shell_exec('git config user.email'));
$defaultPackageSlug = slugify(basename(getcwd()));

// License options
$licenseOptions = [
    'MIT' => 'MIT',
    'Proprietary' => 'proprietary',
    'Apache-2.0' => 'Apache-2.0',
    'BSD-3-Clause' => 'BSD-3-Clause',
];

// Feature options
$featureOptions = [
    'config' => 'Config file',
    'routes_web' => 'Web routes',
    'routes_api' => 'API routes',
    'views' => 'Views',
    'translations' => 'Translations',
    'migrations' => 'Database migrations',
];

// Community files options
$communityOptions = [
    'contributing' => 'CONTRIBUTING.md',
    'security' => 'SECURITY.md',
    'issue_templates' => 'GitHub Issue Templates',
];

// Check if Laravel Prompts form() is available
$usePromptsForm = function_exists('Laravel\\Prompts\\form');

if ($usePromptsForm) {
    // Display intro message about navigation
    \Laravel\Prompts\intro('Laravel Package Template - Package Configurator');
    \Laravel\Prompts\info('💡 Tip: Use CTRL+U to go back to a previous step at any time.');
    echo "\n";
    
    $confirmed = false;
    
    while (! $confirmed) {
        // Collect all data using form() for go-back support
        $responses = \Laravel\Prompts\form()
            ->text(
                label: 'Vendor name',
                default: 'Convertain',
                required: 'Vendor name is required.',
                hint: 'Your company or personal brand name (e.g., Convertain, Spatie)',
                name: 'vendor',
            )
            ->add(function ($responses) use ($defaultPackageSlug) {
                return \Laravel\Prompts\text(
                    label: 'Package name',
                    default: $defaultPackageSlug !== '' ? $defaultPackageSlug : 'laravel-package-name',
                    required: 'Package name is required.',
                    hint: 'Use lowercase with hyphens (e.g., my-awesome-package)',
                );
            }, name: 'package')
            ->text(
                label: 'Package description',
                placeholder: 'A short description of what your package does...',
                hint: 'This will appear in composer.json and README',
                name: 'description',
            )
            ->add(function ($responses) {
                $vendorSlug = slugify($responses['vendor'] ?? 'vendor');
                $packageSlug = slugify($responses['package'] ?? 'package-name');
                return \Laravel\Prompts\text(
                    label: 'Base namespace',
                    default: studly($responses['vendor'] ?? 'Vendor').'\\'.studly($responses['package'] ?? 'Package'),
                    required: 'Namespace is required.',
                    hint: 'PSR-4 namespace for your package classes',
                );
            }, name: 'namespace')
            ->add(function ($responses) use ($gitName) {
                return \Laravel\Prompts\text(
                    label: 'Author name',
                    default: $gitName !== '' ? $gitName : 'Author Name',
                    hint: 'Your name or organization name',
                );
            }, name: 'author_name')
            ->add(function ($responses) use ($gitEmail) {
                return \Laravel\Prompts\text(
                    label: 'Author email',
                    default: $gitEmail !== '' ? $gitEmail : 'support@example.com',
                    hint: 'Contact email for package inquiries',
                );
            }, name: 'author_email')
            ->add(function ($responses) {
                $vendorSlug = slugify($responses['vendor'] ?? 'vendor');
                $packageSlug = slugify($responses['package'] ?? 'package-name');
                return \Laravel\Prompts\text(
                    label: 'GitHub repository URL',
                    default: "https://github.com/{$vendorSlug}/{$packageSlug}",
                    hint: 'URL where the package will be hosted',
                );
            }, name: 'github_url')
            ->select(
                label: 'License',
                options: $licenseOptions,
                default: 'Proprietary',
                hint: 'Choose the license for your package',
                name: 'license',
            )
            ->multiselect(
                label: 'Features to include',
                options: $featureOptions,
                default: ['config', 'routes_web', 'views', 'migrations'],
                hint: 'Select the scaffolding components you need (Space to toggle)',
                name: 'features',
            )
            ->multiselect(
                label: 'Community files to include',
                options: $communityOptions,
                default: ['contributing', 'security', 'issue_templates'],
                hint: 'Documentation for contributors and security reporting',
                name: 'community_files',
            )
            ->select(
                label: 'PHPStan validation level',
                options: [
                    '10' => 'Level 10 - Strictest',
                    '9' => 'Level 9 - Very strict',
                    '8' => 'Level 8 - Report nullable types (recommended)',
                    '7' => 'Level 7 - Union types',
                    '6' => 'Level 6 - Check missing typehints',
                    '5' => 'Level 5 - Check argument types',
                    '4' => 'Level 4 - Dead code',
                    '3' => 'Level 3 - Phpdoc types',
                    '2' => 'Level 2 - Unknown methods',
                    '1' => 'Level 1 - Possibly undefined vars',
                    '0' => 'Level 0 - Basic checks',
                ],
                default: '10',
                hint: 'Higher levels are stricter (0 = basic, 10 = strictest)',
                name: 'phpstan_level',
            )
            ->select(
                label: 'Laravel Pint preset',
                options: [
                    'psr12' => 'PSR-12',
                    'laravel' => 'Laravel  (recommended)',
                    'per' => 'PER Coding Style',
                    'symfony' => 'Symfony',
                    'empty' => 'Empty (no rules)',
                ],
                default: 'laravel',
                hint: 'Code style standard for formatting',
                name: 'pint_preset',
            )
            ->confirm(
                label: 'Install Laravel Boost?',
                default: true,
                yes: 'Yes',
                no: 'No',
                hint: 'AI-powered development tools for your IDE',
                name: 'install_boost',
            )
            ->submit();
        
        // Extract values
        $vendor = $responses['vendor'];
        $package = $responses['package'];
        $packageDescription = $responses['description'] ?? '';
        $namespace = $responses['namespace'];
        $authorName = $responses['author_name'] ?? '';
        $authorEmail = $responses['author_email'] ?? '';
        $githubUrl = $responses['github_url'] ?? '';
        $licenseChoice = $responses['license'];
        $selectedFeatures = $responses['features'] ?? [];
        $selectedCommunityFiles = $responses['community_files'] ?? [];
        $phpstanLevel = $responses['phpstan_level'];
        $pintPreset = $responses['pint_preset'];
        $installBoost = $responses['install_boost'];
        
        // Derive slugs
        $vendorSlug = slugify($vendor !== '' ? $vendor : 'vendor');
        $packageSlug = slugify($package !== '' ? $package : 'package-name');
        $providerClass = studly($package).'ServiceProvider';
        
        // Format features for display
        $enabledFeatures = array_filter($featureOptions, fn($key) => in_array($key, $selectedFeatures), ARRAY_FILTER_USE_KEY);
        $featuresDisplay = empty($enabledFeatures) ? '(none)' : implode(', ', $enabledFeatures);
        
        // Format community files for display
        $enabledCommunity = array_filter($communityOptions, fn($key) => in_array($key, $selectedCommunityFiles), ARRAY_FILTER_USE_KEY);
        $communityDisplay = empty($enabledCommunity) ? '(none)' : implode(', ', $enabledCommunity);
        
        // Show confirmation table
        echo "\n";
        \Laravel\Prompts\info('📋 Configuration Summary');
        \Laravel\Prompts\table(
            headers: ['Setting', 'Value'],
            rows: [
                ['Vendor', $vendor],
                ['Package', $package],
                ['Description', $packageDescription ?: '(not set)'],
                ['Namespace', $namespace],
                ['Provider Class', $providerClass],
                ['Author', $authorName ?: '(not set)'],
                ['Email', $authorEmail ?: '(not set)'],
                ['GitHub URL', $githubUrl ?: '(not set)'],
                ['License', $licenseChoice],
                ['Features', $featuresDisplay],
                ['Community Files', $communityDisplay],
                ['PHPStan Level', $phpstanLevel],
                ['Pint Preset', $pintPreset],
                ['Install Boost', $installBoost ? 'Yes' : 'No'],
            ],
        );
        echo "\n";
        
        // Ask for confirmation
        $confirmed = \Laravel\Prompts\confirm(
            label: 'Proceed with this configuration?',
            default: true,
            yes: 'Yes, continue',
            no: 'No, start over',
            hint: 'Select No to re-enter your package details',
        );
        
        if (! $confirmed) {
            echo "\n";
            \Laravel\Prompts\warning('Starting over...');
            echo "\n";
        }
    }
    
    $licenseIdentifier = $licenseOptions[$licenseChoice];
} else {
    // Fallback to readline-based asks (no form() available)
    echo "\n";
    echo str_repeat('=', 80)."\n";
    echo "  Laravel Package Template - Package Configurator\n";
    echo str_repeat('=', 80)."\n\n";
    
    $vendor = ask('Vendor name', 'Convertain');
    $package = ask('Package name (slug-friendly)', $defaultPackageSlug !== '' ? $defaultPackageSlug : 'laravel-package-name');
    $packageDescription = ask('Package description', 'This package does something awesome.');
    
    $vendorSlug = slugify($vendor !== '' ? $vendor : 'vendor');
    $packageSlug = slugify($package !== '' ? $package : 'package-name');
    $namespace = ask('Base namespace', studly($vendor).'\\'.studly($package));
    $providerClass = studly($package).'ServiceProvider';
    
    $authorName = ask('Author name', $gitName !== '' ? $gitName : 'Convertain Limited');
    $authorEmail = ask('Author email', $gitEmail !== '' ? $gitEmail : 'support@convertain.com');
    $githubUrl = ask('GitHub repository URL', "https://github.com/{$vendorSlug}/{$packageSlug}");
    
    $licenseChoice = ask(
        'License (MIT/Proprietary/Apache-2.0/BSD-3-Clause)',
        'Proprietary',
    );
    
    while (! array_key_exists($licenseChoice, $licenseOptions)) {
        echo 'Invalid choice. Please enter one of: '.implode(', ', array_keys($licenseOptions)).PHP_EOL;
        $licenseChoice = ask(
            'License (MIT/Proprietary/Apache-2.0/BSD-3-Clause)',
            'MIT',
        );
    }
    
    $licenseIdentifier = $licenseOptions[$licenseChoice];
    
    echo "\nSelect features (answer y/n for each):\n";
    $selectedFeatures = [];
    foreach ($featureOptions as $key => $label) {
        $default = in_array($key, ['config', 'routes_web', 'views', 'migrations']);
        if (confirm("Include {$label}?", $default)) {
            $selectedFeatures[] = $key;
        }
    }
    
    echo "\nSelect community files (answer y/n for each):\n";
    $selectedCommunityFiles = [];
    foreach ($communityOptions as $key => $label) {
        if (confirm("Include {$label}?", true)) {
            $selectedCommunityFiles[] = $key;
        }
    }
    
    $phpstanLevel = ask('PHPStan level (0-10, higher is stricter)', '10');
    while (! in_array($phpstanLevel, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], true)) {
        echo "Invalid choice. Please enter a number between 0 and 10.\n";
        $phpstanLevel = ask('PHPStan level (0-10, higher is stricter)', '10');
    }
    
    $pintPreset = ask('Laravel Pint preset (laravel/per/psr12/symfony/empty)', 'psr12');
    while (! in_array($pintPreset, ['laravel', 'per', 'psr12', 'symfony', 'empty'], true)) {
        echo "Invalid choice. Please enter one of: laravel, per, psr12, symfony, empty\n";
        $pintPreset = ask('Laravel Pint preset (laravel/per/psr12/symfony/empty)', 'psr12');
    }
    
    $installBoost = confirm('Install Laravel Boost?', true);
}

// Extract feature flags
$useConfig = in_array('config', $selectedFeatures);
$useRoutesWeb = in_array('routes_web', $selectedFeatures);
$useRoutesApi = in_array('routes_api', $selectedFeatures);
$useViews = in_array('views', $selectedFeatures);
$useTranslations = in_array('translations', $selectedFeatures);
$useMigrations = in_array('migrations', $selectedFeatures);

// Extract community file flags
$useContributing = in_array('contributing', $selectedCommunityFiles);
$useSecurity = in_array('security', $selectedCommunityFiles);
$useIssueTemplates = in_array('issue_templates', $selectedCommunityFiles);

// Map Pint preset to display name for badges
$pintPresetDisplay = match ($pintPreset) {
    'psr12' => 'PSR--12',
    'per' => 'PER',
    'laravel' => 'Laravel',
    'symfony' => 'Symfony',
    'empty' => 'Empty',
    default => strtoupper($pintPreset),
};

$replacements = [
    ':vendor_slug' => $vendorSlug,
    ':package_slug' => $packageSlug,
    ':package_description' => $packageDescription,
    'Vendor\\Package\\Tests' => $namespace.'\\Tests',
    'Vendor\\Package' => $namespace,
    ':namespace' => $namespace,
    ':provider_class' => $providerClass,
    ':github_url' => $githubUrl,
    ':author_name' => $authorName,
    ':author_email' => $authorEmail,
    ':year' => date('Y'),
    ':license' => $licenseIdentifier,
    ':phpstan_level' => $phpstanLevel,
    ':pint_preset' => $pintPreset,
    ':pint_preset_display' => $pintPresetDisplay,
    'package-template' => $packageSlug,
    'PackageServiceProvider' => $providerClass,
    ':package_namespace' => $namespace,
    'github: Convertain' => 'github: '.$vendorSlug,
];

// Helper to run a step with spinner (when available) or fallback output
$runStep = function (string $label, callable $callback) use ($usePromptsForm): mixed {
    if ($usePromptsForm && function_exists('Laravel\\Prompts\\spin')) {
        return \Laravel\Prompts\spin(
            callback: $callback,
            message: $label,
        );
    }
    
    // Fallback: show label and run
    echo "→ {$label}...".PHP_EOL;
    $result = $callback();
    echo "  ✓ Done".PHP_EOL;
    return $result;
};

// Show installation progress header
if ($usePromptsForm) {
    echo "\n";
    \Laravel\Prompts\info('🚀 Installing package...');
    echo "\n";
} else {
    echo "\n".str_repeat('=', 60)."\n";
    echo "  Installing package...\n";
    echo str_repeat('=', 60)."\n\n";
}

$composerPath = __DIR__.'/composer.json';
$providerTarget = null;
$configTarget = null;

// Step 1: Configure package files
$runStep('Configuring package files', function () use (
    $composerPath, $vendorSlug, $packageSlug, $packageDescription, $licenseIdentifier,
    $namespace, $providerClass, $githubUrl, $authorName, $authorEmail,
    $phpstanLevel, $pintPreset, $useConfig, $useRoutesWeb, $useRoutesApi,
    $useViews, $useTranslations, $useMigrations, $replacements,
    &$providerTarget, &$configTarget
) {
    // Update composer.json
    $composer = json_decode((string) file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);
    $composer['name'] = "{$vendorSlug}/{$packageSlug}";
    $composer['description'] = $packageDescription;
    $composer['license'] = $licenseIdentifier;
    $composer['autoload']['psr-4'] = [$namespace.'\\' => 'src/'];
    $composer['autoload-dev']['psr-4'] = [$namespace.'\\Tests\\' => 'tests/'];
    $composer['extra']['laravel']['providers'] = ["{$namespace}\\{$providerClass}"];
    $composer['homepage'] = $githubUrl;
    $composer['authors'] = [['name' => $authorName, 'email' => $authorEmail]];
    file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

    // Update PHPStan level
    $phpstanPath = __DIR__.'/phpstan.neon.dist';
    if (file_exists($phpstanPath)) {
        $content = file_get_contents($phpstanPath);
        if ($content !== false) {
            file_put_contents($phpstanPath, preg_replace('/level:\s*\d+/', 'level: '.$phpstanLevel, $content));
        }
    }

    // Update Pint preset
    $pintPath = __DIR__.'/pint.json';
    if (file_exists($pintPath)) {
        $pintConfig = json_decode((string) file_get_contents($pintPath), true);
        if (is_array($pintConfig)) {
            $pintConfig['preset'] = $pintPreset;
            file_put_contents($pintPath, json_encode($pintConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        }
    }

    // Rename provider
    $providerPath = __DIR__.'/src/PackageServiceProvider.php';
    $providerTarget = __DIR__.'/src/'.$providerClass.'.php';
    if (file_exists($providerPath) && $providerPath !== $providerTarget) {
        rename($providerPath, $providerTarget);
    } elseif (! file_exists($providerTarget)) {
        $providerTarget = $providerPath;
    }

    // Handle config file
    $configPath = __DIR__.'/config/package-template.php';
    $configTarget = __DIR__.'/config/'.($packageSlug !== '' ? $packageSlug : 'package-name').'.php';
    if ($useConfig) {
        if (file_exists($configPath) && $configPath !== $configTarget) {
            rename($configPath, $configTarget);
        } elseif (! file_exists($configTarget)) {
            $configTarget = $configPath;
        }
    } else {
        if (file_exists($configPath)) unlink($configPath);
        if (file_exists($configTarget)) unlink($configTarget);
        $configTarget = null;
    }

    // Handle routes
    $routesDir = __DIR__.'/routes';
    if (! $useRoutesWeb && file_exists($routesDir.'/web.php')) unlink($routesDir.'/web.php');
    if (! $useRoutesApi && file_exists($routesDir.'/api.php')) unlink($routesDir.'/api.php');
    if (is_dir($routesDir) && count(glob($routesDir.'/*')) === 0) @rmdir($routesDir);

    // Helper to remove empty dirs
    $removeDirIfEmpty = function (string $dir): void {
        if (! is_dir($dir)) return;
        $entries = @scandir($dir);
        if ($entries !== false && count(array_diff($entries, ['.', '..'])) === 0) @rmdir($dir);
    };

    // Handle views
    $viewsDir = __DIR__.'/resources/views';
    if (! $useViews && is_dir($viewsDir)) {
        $it = new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
        @rmdir($viewsDir);
    }
    $removeDirIfEmpty(__DIR__.'/resources');

    // Handle translations
    $langDir = __DIR__.'/lang';
    if (! $useTranslations && is_dir($langDir)) {
        $it = new RecursiveDirectoryIterator($langDir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
        @rmdir($langDir);
    }

    // Handle migrations
    $migrationsDir = __DIR__.'/database/migrations';
    if (! $useMigrations && is_dir($migrationsDir)) {
        $it = new RecursiveDirectoryIterator($migrationsDir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
        @rmdir($migrationsDir);
    }
    $removeDirIfEmpty(__DIR__.'/database');
    $removeDirIfEmpty(__DIR__.'/config');

    return true;
});

// Step 2: Build Service Provider
$runStep('Building service provider', function () use ($replacements, $providerTarget, $useConfig, $useRoutesWeb, $useRoutesApi, $useViews, $useTranslations, $useMigrations) {
    $providerTemplatePath = __DIR__.'/data/Provider.php.txt';
    $providerContent = (string) file_get_contents($providerTemplatePath);
    $providerContent = str_replace(array_keys($replacements), array_values($replacements), $providerContent);

    $providerFlags = [
        'config' => $useConfig,
        'routes' => ($useRoutesWeb || $useRoutesApi),
        'routes_web' => $useRoutesWeb,
        'routes_api' => $useRoutesApi,
        'views' => $useViews,
        'translations' => $useTranslations,
        'migrations' => $useMigrations,
        'any_publishing' => ($useConfig || $useViews || $useTranslations || $useMigrations),
    ];

    foreach ($providerFlags as $key => $enabled) {
        $pattern = sprintf('/<!--\s*IF:%s\s*-->[\s\S]*?<!--\s*ENDIF:%s\s*-->/', preg_quote($key, '/'), preg_quote($key, '/'));
        if (! $enabled) {
            $providerContent = (string) preg_replace($pattern, '', $providerContent);
        } else {
            $providerContent = (string) preg_replace([
                '/'.sprintf('<!--\s*IF:%s\s*-->', preg_quote($key, '/')).'/',
                '/'.sprintf('<!--\s*ENDIF:%s\s*-->', preg_quote($key, '/')).'/',
            ], '', $providerContent);
        }
    }

    file_put_contents($providerTarget, $providerContent);
    return true;
});

// Step 3: Install Composer dependencies
$runStep('Installing Composer dependencies', function () {
    runCommandSilent('composer install --prefer-dist --no-interaction --no-progress', 'Composer install failed.');
    runCommandSilent('composer dump-autoload', 'Composer dump-autoload failed.');
    return true;
});

$testbenchBinary = __DIR__.'/vendor/bin/testbench';

// Step 4: Setup Workbench
$runStep('Setting up Workbench environment', function () use ($testbenchBinary) {
    runCommandSilent($testbenchBinary.' workbench:install --no-interaction --ansi', 'Workbench install failed.');
    runCommandSilent($testbenchBinary.' migrate:fresh --no-interaction --ansi', 'Database migration failed.');
    return true;
});

// Step 5: Install Laravel Boost (interactive - cannot use spinner)
if ($installBoost) {
    if ($usePromptsForm) {
        echo "\n";
        \Laravel\Prompts\info('📦 Installing Laravel Boost...');
        echo "\n";
    } else {
        echo "\n→ Installing Laravel Boost...\n";
    }
    runCommand($testbenchBinary.' boost:install --ansi', 'Boost install failed.');
}

// Helper to rewrite MCP configs to use Testbench
$rewriteMcp = function (string $path, bool $waitForCreate = false): void {
    if ($waitForCreate && ! file_exists($path)) {
        // Retry up to 30 times with 100ms intervals (~3s total) for file creation
        for ($i = 0; $i < 30; $i++) {
            usleep(100000); // 100ms
            if (file_exists($path)) {
                break;
            }
        }
    }

    // Check if path exists and is a file (not directory)
    if (! file_exists($path) || is_dir($path)) {
        return;
    }

    $json = file_get_contents($path);
    if ($json === false) {
        return;
    }

    $data = json_decode($json, true);
    if (! is_array($data)) {
        return;
    }

    $updated = false;

    // Helper: update laravel-boost configuration to use vendor/bin/testbench
    $updateLaravelBoostConfig = function (&$config): bool {
        if (! is_array($config)) {
            return false;
        }
        $changed = false;

        // Set command to vendor/bin/testbench
        if (! isset($config['command']) || $config['command'] !== 'vendor/bin/testbench') {
            $config['command'] = 'vendor/bin/testbench';
            $changed = true;
        }

        // Set args to only ['boost:mcp']
        if (! isset($config['args']) || $config['args'] !== ['boost:mcp']) {
            $config['args'] = ['boost:mcp'];
            $changed = true;
        }

        return $changed;
    };

    // Schema 1: mcpServers.laravel-boost (Cursor, Gemini, .mcp.json, .junie/mcp/mcp.json)
    if (isset($data['mcpServers']) && is_array($data['mcpServers'])) {
        if (isset($data['mcpServers']['laravel-boost'])) {
            if ($updateLaravelBoostConfig($data['mcpServers']['laravel-boost'])) {
                $updated = true;
            }
        }
    }

    // Schema 2: servers.laravel-boost (VS Code)
    if (isset($data['servers']) && is_array($data['servers'])) {
        if (isset($data['servers']['laravel-boost'])) {
            if ($updateLaravelBoostConfig($data['servers']['laravel-boost'])) {
                $updated = true;
            }
        }
    }

    // Schema 3: top-level laravel-boost (fallback)
    if (isset($data['laravel-boost'])) {
        if ($updateLaravelBoostConfig($data['laravel-boost'])) {
            $updated = true;
        }
    }

    // Schema 4: clients array (fallback)
    if (isset($data['clients']) && is_array($data['clients'])) {
        foreach ($data['clients'] as &$client) {
            if (isset($client['laravel-boost'])) {
                if ($updateLaravelBoostConfig($client['laravel-boost'])) {
                    $updated = true;
                }
            }
        }
        unset($client);
    }

    if ($updated) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
};

// Step 6: Configure MCP and finalize files
$runStep('Configuring MCP and finalizing files', function () use (
    $installBoost, $rewriteMcp, $replacements, $providerTarget, $configTarget,
    $useConfig, $useRoutesWeb, $useRoutesApi, $useViews, $useTranslations, $useMigrations,
    $licenseChoice, $useContributing, $useSecurity, $useIssueTemplates
) {
    // Rewrite MCP configs if Boost is installed
    if ($installBoost) {
        $rewriteMcp(__DIR__.'/.vscode/mcp.json', true);
        $rewriteMcp(__DIR__.'/.cursor/mcp.json', false);
        $rewriteMcp(__DIR__.'/.gemini/settings.json', false);
        $rewriteMcp(__DIR__.'/.junie/mcp/mcp.json', false);
        $rewriteMcp(__DIR__.'/.mcp.json', false);
    }

    // Replace placeholders in files
    $files = array_values(array_filter([
        __DIR__.'/README.md',
        __DIR__.'/phpunit.xml.dist',
        $providerTarget,
        $configTarget,
        __DIR__.'/tests/ExampleTest.php',
        __DIR__.'/tests/TestCase.php',
        __DIR__.'/.github/FUNDING.yml',
        __DIR__.'/LICENSE.md',
        __DIR__.'/.vscode/mcp.json',
    ]));
    replaceInFiles($files, $replacements);

    // Build package README from template
    $readmeTemplate = __DIR__.'/README.package.md';
    if (file_exists($readmeTemplate)) {
        $content = (string) file_get_contents($readmeTemplate);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        $flags = [
            'config' => $useConfig,
            'routes' => ($useRoutesWeb || $useRoutesApi),
            'routes_web' => $useRoutesWeb,
            'routes_api' => $useRoutesApi,
            'views' => $useViews,
            'translations' => $useTranslations,
            'migrations' => $useMigrations,
        ];

        foreach ($flags as $key => $enabled) {
            $pattern = sprintf('/<!--\\s*IF:%s\\s*-->[\\s\\S]*?<!--\\s*ENDIF:%s\\s*-->/', preg_quote($key, '/'), preg_quote($key, '/'));
            if (! $enabled) {
                $content = (string) preg_replace($pattern, '', $content);
            } else {
                $content = (string) preg_replace(['/' . sprintf('<!--\\s*IF:%s\\s*-->', preg_quote($key, '/')) . '/', '/' . sprintf('<!--\\s*ENDIF:%s\\s*-->', preg_quote($key, '/')) . '/'], '', $content);
            }
        }

        file_put_contents(__DIR__.'/README.md', $content);
        @unlink($readmeTemplate);
    }

    // Generate LICENSE.md
    $licenseTemplatePath = __DIR__.'/data/licenses/'.$licenseChoice.'.txt';
    if (! file_exists($licenseTemplatePath)) {
        $licenseTemplatePath = __DIR__.'/data/licenses/MIT.txt';
    }
    $licenseContent = (string) file_get_contents($licenseTemplatePath);
    $licenseContent = str_replace(array_keys($replacements), array_values($replacements), $licenseContent);
    file_put_contents(__DIR__.'/LICENSE.md', $licenseContent.PHP_EOL);

    // Generate community files
    if ($useContributing) {
        $contributingTemplate = __DIR__.'/data/CONTRIBUTING.md.txt';
        if (file_exists($contributingTemplate)) {
            $content = (string) file_get_contents($contributingTemplate);
            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
            file_put_contents(__DIR__.'/CONTRIBUTING.md', $content);
        }
    } else {
        if (file_exists(__DIR__.'/CONTRIBUTING.md')) @unlink(__DIR__.'/CONTRIBUTING.md');
    }

    if ($useSecurity) {
        $securityTemplate = __DIR__.'/data/SECURITY.md.txt';
        if (file_exists($securityTemplate)) {
            $content = (string) file_get_contents($securityTemplate);
            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
            file_put_contents(__DIR__.'/SECURITY.md', $content);
        }
    } else {
        if (file_exists(__DIR__.'/SECURITY.md')) @unlink(__DIR__.'/SECURITY.md');
    }

    if ($useIssueTemplates) {
        $issueTemplateDir = __DIR__.'/.github/ISSUE_TEMPLATE';
        $dataIssueTemplateDir = __DIR__.'/data/github/ISSUE_TEMPLATE';
        if (is_dir($dataIssueTemplateDir)) {
            if (! is_dir($issueTemplateDir)) mkdir($issueTemplateDir, 0755, true);
            foreach (['bug_report.md', 'feature_request.md'] as $templateFile) {
                $sourcePath = $dataIssueTemplateDir.'/'.$templateFile;
                if (file_exists($sourcePath)) {
                    $content = (string) file_get_contents($sourcePath);
                    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
                    file_put_contents($issueTemplateDir.'/'.$templateFile, $content);
                }
            }
        }
    } else {
        $issueTemplateDir = __DIR__.'/.github/ISSUE_TEMPLATE';
        if (is_dir($issueTemplateDir)) {
            foreach (['bug_report.md', 'feature_request.md'] as $templateFile) {
                $filePath = $issueTemplateDir.'/'.$templateFile;
                if (file_exists($filePath)) @unlink($filePath);
            }
            $entries = @scandir($issueTemplateDir);
            if ($entries !== false && count(array_diff($entries, ['.', '..'])) === 0) @rmdir($issueTemplateDir);
        }
    }

    return true;
});

// Step 7: Cleanup
$runStep('Cleaning up temporary files', function () {
    runCommandSilent('composer dump-autoload', 'Composer dump-autoload failed.');

    // Remove the data templates directory
    $dataDir = __DIR__.'/data';
    if (is_dir($dataDir)) {
        $it = new RecursiveDirectoryIterator($dataDir, FilesystemIterator::SKIP_DOTS);
        $filesIt = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($filesIt as $fsItem) {
            if ($fsItem->isDir()) {
                @rmdir($fsItem->getPathname());
            } else {
                @unlink($fsItem->getPathname());
            }
        }
        @rmdir($dataDir);
    }

    // Remove template git remote if present
    $templateRepo = 'Convertain/laravel-package-template';
    if (is_dir(__DIR__.'/.git')) {
        $remotesOutput = shell_exec('cd '.escapeshellarg(__DIR__).' && git remote -v 2>/dev/null');
        if (is_string($remotesOutput) && trim($remotesOutput) !== '') {
            $lines = explode("\n", trim($remotesOutput));
            $removed = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || ! preg_match('/^(\S+)\s+(\S+)\s+\(fetch\)$/', $line, $matches)) continue;
                $remoteName = $matches[1];
                $remoteUrl = $matches[2];
                if (isset($removed[$remoteName])) continue;
                if (str_contains($remoteUrl, $templateRepo)) {
                    shell_exec('git remote remove '.escapeshellarg($remoteName).' 2>/dev/null');
                    $removed[$remoteName] = true;
                }
            }
        }
    }

    return true;
});

// Remove installer (it cannot be re-run after setup completes)
unlink(__FILE__);

// Step 8: Run code quality checks (shows output)
if ($usePromptsForm) {
    echo "\n";
    \Laravel\Prompts\info('🔍 Running code quality checks...');
    echo "\n";
} else {
    echo "\n".str_repeat('=', 60)."\n";
    echo "  Running code quality checks...\n";
    echo str_repeat('=', 60)."\n\n";
}

passthru('composer lint', $lintExit);
passthru('composer analyse', $analyseExit);

// Final success message
if ($usePromptsForm) {
    echo "\n";
    \Laravel\Prompts\outro("✅ Package '{$packageSlug}' has been configured successfully!");
    echo "\n";
    \Laravel\Prompts\info("Next steps:");
    echo "  → Run 'composer serve' to start the development server\n";
    echo "  → Run 'composer test' to run the test suite\n";
    echo "\n";
} else {
    echo "\n".str_repeat('=', 60)."\n";
    echo "  ✅ Package '{$packageSlug}' configured successfully!\n";
    echo str_repeat('=', 60)."\n\n";
    echo "Next steps:\n";
    echo "  → Run 'composer serve' to start the development server\n";
    echo "  → Run 'composer test' to run the test suite\n\n";
}

exit(0);
