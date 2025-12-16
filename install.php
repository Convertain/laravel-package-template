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
            ->confirm(
                label: 'Install Laravel Boost?',
                default: true,
                yes: 'Yes',
                no: 'No',
                hint: 'AI-powered development tools for your IDE',
                name: 'install_boost',
            )
            ->confirm(
                label: 'Remove install.php after setup?',
                default: true,
                yes: 'Yes',
                no: 'No',
                hint: 'Recommended: remove the installer for a clean package',
                name: 'remove_installer',
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
        $installBoost = $responses['install_boost'];
        $removeInstaller = $responses['remove_installer'];
        
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
                ['Install Boost', $installBoost ? 'Yes' : 'No'],
                ['Remove Installer', $removeInstaller ? 'Yes' : 'No'],
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
    
    $installBoost = confirm('Install Laravel Boost?', true);
    $removeInstaller = confirm('Remove install.php after setup?', true);
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
    'package-template' => $packageSlug,
    'PackageServiceProvider' => $providerClass,
    ':package_namespace' => $namespace,
    'github: Convertain' => 'github: '.$vendorSlug,
];

$composerPath = __DIR__.'/composer.json';
$composer = json_decode((string) file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);

$composer['name'] = "{$vendorSlug}/{$packageSlug}";
$composer['description'] = $packageDescription;
$composer['license'] = $licenseIdentifier;
$composer['autoload']['psr-4'] = [$namespace.'\\' => 'src/'];
$composer['autoload-dev']['psr-4'] = [
    $namespace.'\\Tests\\' => 'tests/',
];
$composer['extra']['laravel']['providers'] = ["{$namespace}\\{$providerClass}"];
$composer['homepage'] = $githubUrl;
$composer['authors'] = [
    [
        'name' => $authorName,
        'email' => $authorEmail,
    ],
];

file_put_contents(
    $composerPath,
    json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
);

$providerPath = __DIR__.'/src/PackageServiceProvider.php';
$providerTarget = __DIR__.'/src/'.$providerClass.'.php';

if (file_exists($providerPath) && $providerPath !== $providerTarget) {
    rename($providerPath, $providerTarget);
} elseif (! file_exists($providerTarget)) {
    $providerTarget = $providerPath;
}

$configPath = __DIR__.'/config/package-template.php';
$configTarget = __DIR__.'/config/'.($packageSlug !== '' ? $packageSlug : 'package-name').'.php';

if ($useConfig) {
    if (file_exists($configPath) && $configPath !== $configTarget) {
        rename($configPath, $configTarget);
    } elseif (! file_exists($configTarget)) {
        $configTarget = $configPath;
    }
} else {
    if (file_exists($configPath)) {
        unlink($configPath);
    }
    if (file_exists($configTarget)) {
        unlink($configTarget);
    }
    $configTarget = null;
}

$routesDir = __DIR__.'/routes';

if (! $useRoutesWeb && file_exists($routesDir.'/web.php')) {
    unlink($routesDir.'/web.php');
}

if (! $useRoutesApi && file_exists($routesDir.'/api.php')) {
    unlink($routesDir.'/api.php');
}

if (is_dir($routesDir) && count(glob($routesDir.'/*')) === 0) {
    @rmdir($routesDir);
}

$removeDirIfEmpty = function (string $dir): void {
    if (! is_dir($dir)) {
        return;
    }
    $entries = @scandir($dir);
    if ($entries === false) {
        return;
    }
    $nonDots = array_diff($entries, ['.', '..']);
    if (count($nonDots) === 0) {
        @rmdir($dir);
    }
};

$viewsDir = __DIR__.'/resources/views';
if (! $useViews && is_dir($viewsDir)) {
    passthru('rm -rf '.escapeshellarg($viewsDir));
}
$removeDirIfEmpty(__DIR__.'/resources');

$langDir = __DIR__.'/lang';
if (! $useTranslations && is_dir($langDir)) {
    passthru('rm -rf '.escapeshellarg($langDir));
}

$migrationsDir = __DIR__.'/database/migrations';
if (! $useMigrations && is_dir($migrationsDir)) {
    passthru('rm -rf '.escapeshellarg($migrationsDir));
}
$removeDirIfEmpty(__DIR__.'/database');

$configDir = __DIR__.'/config';
$removeDirIfEmpty($configDir);

// Build Service Provider from template with conditional sections
$providerTemplatePath = __DIR__.'/data/Provider.php.txt';
$providerContent = (string) file_get_contents($providerTemplatePath);

// Apply simple replacements
$providerContent = str_replace(array_keys($replacements), array_values($replacements), $providerContent);

// Conditional sections handling (same pattern as README)
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

$files = array_values(array_filter([
    __DIR__.'/README.md',
    __DIR__.'/phpunit.xml.dist',
    $providerTarget,
    $configTarget,
    __DIR__.'/tests/ExampleTest.php',
    __DIR__.'/tests/TestCase.php',
    __DIR__.'/.github/FUNDING.yml',
    __DIR__.'/LICENSE.md',
    __DIR__.'/workbench/config/workbench.php',
    __DIR__.'/workbench/routes/web.php',
    __DIR__.'/workbench/routes/console.php',
    __DIR__.'/.vscode/mcp.json',
]));

runCommand('composer install --prefer-dist --no-interaction --no-progress', 'Composer install failed.');
runCommand('composer dump-autoload', 'Composer dump-autoload failed.');

$testbenchBinary = __DIR__.'/vendor/bin/testbench';

runCommand($testbenchBinary.' workbench:install --no-interaction --ansi', 'Workbench install failed.');
runCommand($testbenchBinary.' migrate:fresh --no-interaction --ansi', 'Database migration failed.');

// Phase 2: Install Laravel Boost interactively, then fix MCP config for Testbench
if ($installBoost) {
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
        echo 'Updated MCP config: '.str_replace(__DIR__.'/', '', $path).PHP_EOL;
    }
};

if ($installBoost) {
    // Update VS Code (wait for file creation), Cursor, Gemini, Junie, and generic .mcp.json if present
    $rewriteMcp(__DIR__.'/.vscode/mcp.json', true);
    $rewriteMcp(__DIR__.'/.cursor/mcp.json', false);
    $rewriteMcp(__DIR__.'/.gemini/settings.json', false);
    $rewriteMcp(__DIR__.'/.junie/mcp/mcp.json', false);
    $rewriteMcp(__DIR__.'/.mcp.json', false);
} else {
    echo "Skipped Laravel Boost installation; MCP configs were not modified.".PHP_EOL;
}

replaceInFiles($files, $replacements);

// Build package README from template if present
$readmeTemplate = __DIR__.'/README.package.md';
if (file_exists($readmeTemplate)) {
    $content = (string) file_get_contents($readmeTemplate);

    // Apply simple replacements
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);

    // Conditional sections handling
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
            // Remove the markers but keep the content
            $content = (string) preg_replace(['/' . sprintf('<!--\\s*IF:%s\\s*-->', preg_quote($key, '/')) . '/', '/' . sprintf('<!--\\s*ENDIF:%s\\s*-->', preg_quote($key, '/')) . '/'], '', $content);
        }
    }

    file_put_contents(__DIR__.'/README.md', $content);
    @unlink($readmeTemplate);
}

$year = date('Y');
$licenseTemplatePath = __DIR__.'/data/licenses/'.$licenseChoice.'.txt';
if (! file_exists($licenseTemplatePath)) {
    $licenseTemplatePath = __DIR__.'/data/licenses/MIT.txt';
}
$licenseContent = (string) file_get_contents($licenseTemplatePath);
$licenseContent = str_replace(array_keys($replacements), array_values($replacements), $licenseContent);
file_put_contents(__DIR__.'/LICENSE.md', $licenseContent.PHP_EOL);

// Generate community files from templates
if ($useContributing) {
    $contributingTemplate = __DIR__.'/data/CONTRIBUTING.md.txt';
    if (file_exists($contributingTemplate)) {
        $content = (string) file_get_contents($contributingTemplate);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        file_put_contents(__DIR__.'/CONTRIBUTING.md', $content);
        echo "Created CONTRIBUTING.md\n";
    }
} else {
    // Remove CONTRIBUTING.md if it exists from template
    if (file_exists(__DIR__.'/CONTRIBUTING.md')) {
        @unlink(__DIR__.'/CONTRIBUTING.md');
    }
}

if ($useSecurity) {
    $securityTemplate = __DIR__.'/data/SECURITY.md.txt';
    if (file_exists($securityTemplate)) {
        $content = (string) file_get_contents($securityTemplate);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        file_put_contents(__DIR__.'/SECURITY.md', $content);
        echo "Created SECURITY.md\n";
    }
} else {
    // Remove SECURITY.md if it exists from template
    if (file_exists(__DIR__.'/SECURITY.md')) {
        @unlink(__DIR__.'/SECURITY.md');
    }
}

if ($useIssueTemplates) {
    $issueTemplateDir = __DIR__.'/.github/ISSUE_TEMPLATE';
    $dataIssueTemplateDir = __DIR__.'/data/github/ISSUE_TEMPLATE';
    
    if (is_dir($dataIssueTemplateDir)) {
        if (! is_dir($issueTemplateDir)) {
            mkdir($issueTemplateDir, 0755, true);
        }
        
        foreach (['bug_report.md', 'feature_request.md'] as $templateFile) {
            $sourcePath = $dataIssueTemplateDir.'/'.$templateFile;
            if (file_exists($sourcePath)) {
                $content = (string) file_get_contents($sourcePath);
                $content = str_replace(array_keys($replacements), array_values($replacements), $content);
                file_put_contents($issueTemplateDir.'/'.$templateFile, $content);
                echo "Created .github/ISSUE_TEMPLATE/{$templateFile}\n";
            }
        }
    }
} else {
    // Remove issue templates if they exist
    $issueTemplateDir = __DIR__.'/.github/ISSUE_TEMPLATE';
    if (is_dir($issueTemplateDir)) {
        foreach (['bug_report.md', 'feature_request.md'] as $templateFile) {
            $filePath = $issueTemplateDir.'/'.$templateFile;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        // Remove directory if empty
        $entries = @scandir($issueTemplateDir);
        if ($entries !== false && count(array_diff($entries, ['.', '..'])) === 0) {
            @rmdir($issueTemplateDir);
        }
    }
}

runCommand('composer dump-autoload', 'Composer dump-autoload failed.');

// Cleanup: remove the data templates directory now that installation is complete
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

// Remove template git remote if present (only when this repo was cloned directly)
$templateRepo = 'Convertain/laravel-package-template';
if (is_dir(__DIR__.'/.git')) {
    $remotesOutput = shell_exec('cd '.escapeshellarg(__DIR__).' && git remote -v 2>/dev/null');
    if (is_string($remotesOutput) && trim($remotesOutput) !== '') {
        $lines = explode("\n", trim($remotesOutput));
        $removed = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (! preg_match('/^(\S+)\s+(\S+)\s+\(fetch\)$/', $line, $matches)) {
                continue;
            }
            $remoteName = $matches[1];
            $remoteUrl = $matches[2];
            if (isset($removed[$remoteName])) {
                continue;
            }
            if (str_contains($remoteUrl, $templateRepo)) {
                echo "Removing template git remote '{$remoteName}'...".PHP_EOL;
                runCommand('git remote remove '.$remoteName, 'Failed to remove template git remote: '.$remoteName);
                $removed[$remoteName] = true;
            }
        }
    }
}

// Remove installer if previously confirmed
if (isset($removeInstaller) && $removeInstaller) {
    unlink(__FILE__);
}

// Run composer lint and analyse
echo "\n".str_repeat('=', 80)."\n";
echo 'Running code quality checks...\n';
echo str_repeat('=', 80)."\n\n";

passthru('composer lint', $lintExit);
passthru('composer analyse', $analyseExit);

exit(0);
