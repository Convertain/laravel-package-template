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

// Display header
echo "\n";
echo str_repeat('=', 80)."\n";
echo "  Laravel Package Template - Package Configurator\n";
echo str_repeat('=', 80)."\n\n";

$gitName = trim((string) shell_exec('git config user.name'));
$gitEmail = trim((string) shell_exec('git config user.email'));

// Use Prompts for nice text inputs if available
$usePrompts = function_exists('Laravel\\Prompts\\text');

if ($usePrompts) {
    $vendor = \Laravel\Prompts\text(
        label: 'Vendor name',
        default: 'Convertain',
        required: true,
    );
    
    $defaultPackageSlug = slugify(basename(getcwd()));
    $package = \Laravel\Prompts\text(
        label: 'Package name (slug-friendly)',
        default: $defaultPackageSlug !== '' ? $defaultPackageSlug : 'laravel-package-name',
        required: true,
    );
    
    $packageDescription = \Laravel\Prompts\text(
        label: 'Package description',
        placeholder: 'E.g. This package does something awesome.',
    );
    
    $vendorSlug = slugify($vendor !== '' ? $vendor : 'vendor');
    $packageSlug = slugify($package !== '' ? $package : 'package-name');
    
    $namespace = \Laravel\Prompts\text(
        label: 'Base namespace',
        default: studly($vendor).'\\'.studly($package),
        required: true,
    );
    
    $providerClass = studly($package).'ServiceProvider';
    
    $authorName = \Laravel\Prompts\text(
        label: 'Author name',
        default: $gitName !== '' ? $gitName : 'Author Name',
    );
    
    $authorEmail = \Laravel\Prompts\text(
        label: 'Author email',
        default: $gitEmail !== '' ? $gitEmail : 'support@example.com',
    );
    
    $githubUrl = \Laravel\Prompts\text(
        label: 'GitHub repository URL',
        default: "https://github.com/{$vendorSlug}/{$packageSlug}",
    );
    
    $licenseOptions = [
        'MIT' => 'MIT',
        'Proprietary' => 'proprietary',
        'Apache-2.0' => 'Apache-2.0',
        'BSD-3-Clause' => 'BSD-3-Clause',
    ];
    
    $licenseChoice = \Laravel\Prompts\select(
        label: 'License',
        options: $licenseOptions,
        default: 'Proprietary',
    );
} else {
    // Fallback to readline-based asks
    $vendor = ask('Vendor name', 'Convertain');
    $defaultPackageSlug = slugify(basename(getcwd()));
    $package = ask('Package name (slug-friendly)', $defaultPackageSlug !== '' ? $defaultPackageSlug : 'laravel-package-name');
    $packageDescription = ask('Package description', 'This package does something awesome.');
    
    $vendorSlug = slugify($vendor !== '' ? $vendor : 'vendor');
    $packageSlug = slugify($package !== '' ? $package : 'package-name');
    $namespace = ask('Base namespace', studly($vendor).'\\'.studly($package));
    $providerClass = studly($package).'ServiceProvider';
    
    $authorName = ask('Author name', 'Convertain Limited');
    $authorEmail = ask('Author email', 'support@convertain.com');
    $githubUrl = ask('GitHub repository URL', "https://github.com/{$vendorSlug}/{$packageSlug}");
    
    $licenseOptions = [
        'MIT' => 'MIT',
        'Proprietary' => 'proprietary',
        'Apache-2.0' => 'Apache-2.0',
        'BSD-3-Clause' => 'BSD-3-Clause',
    ];
    
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
}

if (! array_key_exists($licenseChoice, $licenseOptions)) {
    echo 'Invalid choice. Please enter one of: '.implode(', ', array_keys($licenseOptions)).PHP_EOL;
    $licenseChoice = ask(
        'License (MIT/Proprietary/Apache-2.0/BSD-3-Clause)',
        'MIT',
    );
}

$licenseIdentifier = $licenseOptions[$licenseChoice];

// Multi-select features
echo "\nSelect features to include:\n";

$selectedFeatures = function_exists('Laravel\\Prompts\\multiselect') 
    ? \Laravel\Prompts\multiselect(
        label: 'Features',
        options: [
            'config' => 'Config file',
            'routes_web' => 'Web routes',
            'routes_api' => 'API routes',
            'views' => 'Views',
            'translations' => 'Translations',
            'migrations' => 'Database migrations',
        ],
        default: ['config', 'routes_web', 'views', 'translations', 'migrations'],
    )
    : [];

// No fallback: empty selection means all features disabled

$useConfig = in_array('config', $selectedFeatures);
$useRoutesWeb = in_array('routes_web', $selectedFeatures);
$useRoutesApi = in_array('routes_api', $selectedFeatures);
$useViews = in_array('views', $selectedFeatures);
$useTranslations = in_array('translations', $selectedFeatures);
$useMigrations = in_array('migrations', $selectedFeatures);

// Install Boost confirmation with styled prompt
$installBoost = function_exists('Laravel\\Prompts\\confirm')
    ? \Laravel\Prompts\confirm('Install Laravel Boost?', default: true)
    : confirm('Install Laravel Boost?', true);

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

if (confirm('Remove install.php after setup?', true)) {
    unlink(__FILE__);
}

// Run composer lint and analyse
echo "\n".str_repeat('=', 80)."\n";
echo 'Running code quality checks...\n';
echo str_repeat('=', 80)."\n\n";

passthru('composer lint', $lintExit);
passthru('composer analyse', $analyseExit);

exit(0);
