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

$gitName = trim((string) shell_exec('git config user.name'));
$gitEmail = trim((string) shell_exec('git config user.email'));

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

$licenseIdentifier = $licenseOptions[$licenseChoice];

$useConfig = confirm('Include config file?', true);
$useRoutesWeb = confirm('Include web routes?', true);
$useRoutesApi = confirm('Include API routes?', false);
$useViews = confirm('Include views?', true);
$useTranslations = confirm('Include translations?', true);
$useMigrations = confirm('Include database migrations?', true);

$replacements = [
    ':vendor_slug' => $vendorSlug,
    ':package_slug' => $packageSlug,
    ':package_description' => $packageDescription,
    'Vendor\\Package\\Tests' => $namespace.'\\Tests',
    'Vendor\\Package' => $namespace,
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

$viewsDir = __DIR__.'/resources/views';
if (! $useViews && is_dir($viewsDir)) {
    passthru('rm -rf '.escapeshellarg($viewsDir));
}

$langDir = __DIR__.'/lang';
if (! $useTranslations && is_dir($langDir)) {
    passthru('rm -rf '.escapeshellarg($langDir));
}

$migrationsDir = __DIR__.'/database/migrations';
if (! $useMigrations && is_dir($migrationsDir)) {
    passthru('rm -rf '.escapeshellarg($migrationsDir));
}

$providerContent = "<?php\n\n".
    "declare(strict_types=1);\n\n".
    "namespace {$namespace};\n\n".
    "use Illuminate\\Support\\ServiceProvider;\n\n".
    "class {$providerClass} extends ServiceProvider\n".
    "{\n".
    "    public function register(): void\n".
    "    {\n".
    ($useConfig ? "        \\$this->mergeConfigFrom(\n            __DIR__.'/../config/{$packageSlug}.php',\n            '{$packageSlug}',\n        );\n" : '').
    "    }\n\n".
    "    public function boot(): void\n".
    "    {\n".
    ($useRoutesWeb || $useRoutesApi ? "        \\$this->registerRoutes();\n" : '').
    ($useViews ? "        \\$this->registerViews();\n" : '').
    ($useTranslations ? "        \\$this->registerTranslations();\n" : '').
    ($useMigrations ? "        \\$this->registerMigrations();\n" : '').
    ($useConfig || $useViews || $useTranslations || $useMigrations ? "        \\$this->registerPublishing();\n" : '').
    "    }\n\n".
    ($useRoutesWeb || $useRoutesApi ? "    protected function registerRoutes(): void\n    {\n".
        ($useRoutesWeb ? "        if (file_exists(__DIR__.'/../routes/web.php')) {\n            \\$this->loadRoutesFrom(__DIR__.'/../routes/web.php');\n        }\n\n" : '').
        ($useRoutesApi ? "        if (file_exists(__DIR__.'/../routes/api.php')) {\n            \\$this->loadRoutesFrom(__DIR__.'/../routes/api.php');\n        }\n" : '').
    "    }\n\n" : '').
    ($useViews ? "    protected function registerViews(): void\n    {\n        if (is_dir(__DIR__.'/../resources/views')) {\n            \\$this->loadViewsFrom(__DIR__.'/../resources/views', '{$packageSlug}');\n        }\n    }\n\n" : '').
    ($useTranslations ? "    protected function registerTranslations(): void\n    {\n        if (is_dir(__DIR__.'/../lang')) {\n            \\$this->loadTranslationsFrom(__DIR__.'/../lang', '{$packageSlug}');\n            \\$this->loadJsonTranslationsFrom(__DIR__.'/../lang');\n        }\n    }\n\n" : '').
    ($useMigrations ? "    protected function registerMigrations(): void\n    {\n        if (is_dir(__DIR__.'/../database/migrations')) {\n            \\$this->loadMigrationsFrom(__DIR__.'/../database/migrations');\n        }\n    }\n\n" : '').
    ($useConfig || $useViews || $useTranslations || $useMigrations ? "    protected function registerPublishing(): void\n    {\n        if (! \\$this->app->runningInConsole()) {\n            return;\n        }\n\n".
        ($useConfig ? "        if (file_exists(__DIR__.'/../config/{$packageSlug}.php')) {\n            \\$this->publishes([\n                __DIR__.'/../config/{$packageSlug}.php' => config_path('{$packageSlug}.php'),\n            ], '{$packageSlug}-config');\n        }\n\n" : '').
        ($useViews ? "        if (is_dir(__DIR__.'/../resources/views')) {\n            \\$this->publishes([\n                __DIR__.'/../resources/views' => resource_path('views/vendor/{$packageSlug}'),\n            ], '{$packageSlug}-views');\n        }\n\n" : '').
        ($useTranslations ? "        if (is_dir(__DIR__.'/../lang')) {\n            \\$targetLangPath = method_exists(\$this->app, 'langPath')\n                ? \\$this->app->langPath('vendor/{$packageSlug}')\n                : resource_path('lang/vendor/{$packageSlug}');\n\n            \\$this->publishes([\n                __DIR__.'/../lang' => \\$targetLangPath,\n            ], '{$packageSlug}-lang');\n        }\n\n" : '').
        ($useMigrations ? "        if (is_dir(__DIR__.'/../database/migrations')) {\n            \\$this->publishes([\n                __DIR__.'/../database/migrations' => database_path('migrations'),\n            ], '{$packageSlug}-migrations');\n        }\n" : '').
    "    }\n" : '').
    "}\n";

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
runCommand($testbenchBinary.' boost:install --ansi', 'Boost install failed.');

// Ensure .vscode/mcp.json uses vendor/bin/testbench for package context
$mcpPath = __DIR__.'/.vscode/mcp.json';
if (file_exists($mcpPath)) {
    $json = file_get_contents($mcpPath);
    if ($json !== false) {
        $data = json_decode($json, true);
        if (is_array($data) && isset($data['clients']) && is_array($data['clients'])) {
            foreach ($data['clients'] as &$client) {
                if (isset($client['command'])) {
                    $client['command'] = 'vendor/bin/testbench';
                    $client['args'] = [];
                }
            }
            file_put_contents($mcpPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            echo 'Updated .vscode/mcp.json to use vendor/bin/testbench.'.PHP_EOL;
        }
    }
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

if ($licenseChoice === 'MIT') {
    $licenseContent = <<<LICENSE
# MIT License

Copyright (c) {$year} {$authorName}

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
LICENSE;
} elseif ($licenseChoice === 'Proprietary') {
    $licenseContent = <<<LICENSE
# Proprietary License

Copyright (c) {$year} {$authorName}. All rights reserved.

This software and associated documentation files (the "Software") are the
confidential and proprietary information of the copyright holder. You may not
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software except as explicitly permitted in a separate written agreement.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
COPYRIGHT HOLDER BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
LICENSE;
} elseif ($licenseChoice === 'Apache-2.0') {
    $licenseContent = <<<LICENSE
# Apache License
Version 2.0, January 2004
http://www.apache.org/licenses/

Copyright (c) {$year} {$authorName}

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
LICENSE;
} else { // BSD-3-Clause
    $licenseContent = <<<LICENSE
# BSD 3-Clause License

Copyright (c) {$year} {$authorName}
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.
3. Neither the name of the copyright holder nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
LICENSE;
}

file_put_contents(__DIR__.'/LICENSE.md', $licenseContent.PHP_EOL);

runCommand('composer dump-autoload', 'Composer dump-autoload failed.');

if (confirm('Remove install.php after setup?', true)) {
    unlink(__FILE__);
}

exit(0);
