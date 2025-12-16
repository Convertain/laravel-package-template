<?php

declare(strict_types=1);

namespace Workbench\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BoostSetupCommand extends Command
{
    protected $signature = 'boost:setup {--skip-boost : Skip Laravel Boost installation entirely}';

    protected $description = 'Set up Laravel Boost with interactive agent and IDE selection';

    public function handle(): int
    {
        if ($this->option('skip-boost')) {
            $this->info('Skipping Laravel Boost setup.');
            return self::SUCCESS;
        }

        // Determine what to install
        $installMcp = $this->confirm('Install Laravel Boost MCP server?', true);
        $installGuidelines = $this->confirm('Install Laravel Boost AI guidelines?', true);

        if (!$installMcp && !$installGuidelines) {
            $this->info('Skipping Laravel Boost setup.');
            return self::SUCCESS;
        }

        $boostArgs = ['boost:install'];

        if (!$installGuidelines) {
            $boostArgs[] = '--ignore-guidelines';
        }
        if (!$installMcp) {
            $boostArgs[] = '--ignore-mcp';
        }

        $boostArgs[] = '--ansi';

        // Run boost:install with full interactivity (no --no-interaction flag)
        $result = Process::run(
            ['php', 'artisan', ...$boostArgs],
            cwd: base_path(),
        );

        if (!$result->successful()) {
            $this->error('Laravel Boost installation failed.');
            $this->line($result->errorOutput());
            return self::FAILURE;
        }

        // Fix mcp.json to use testbench instead of artisan for package context
        if ($installMcp) {
            $this->fixMcpJsonForTestbench();
        }

        $this->info('Laravel Boost setup completed successfully.');
        return self::SUCCESS;
    }

    private function fixMcpJsonForTestbench(): void
    {
        $mpcConfigPath = base_path('.vscode/mcp.json');

        if (!file_exists($mpcConfigPath)) {
            return;
        }

        try {
            $mpcConfig = json_decode((string) file_get_contents($mpcConfigPath), true, flags: JSON_THROW_ON_ERROR);

            if (isset($mpcConfig['mcpServers']['laravel-boost'])) {
                $mpcConfig['mcpServers']['laravel-boost'] = [
                    'command' => 'vendor/bin/testbench',
                    'args' => ['boost:mcp'],
                ];

                file_put_contents(
                    $mpcConfigPath,
                    json_encode($mpcConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
                );

                $this->info('Fixed MCP configuration to use testbench for package context.');
            }
        } catch (\Exception $e) {
            $this->warn("Could not update MCP configuration: {$e->getMessage()}");
        }
    }
}
