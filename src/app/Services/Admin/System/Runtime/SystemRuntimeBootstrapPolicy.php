<?php

namespace App\Services\Admin\System\Runtime;

use Illuminate\Contracts\Foundation\Application;

class SystemRuntimeBootstrapPolicy
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function maySkipDatabaseInspectionFailure(?array $argv = null): bool
    {
        if (! $this->app->runningInConsole()) {
            return false;
        }

        return $this->commandName(
                $argv ?? $this->serverArguments(),
            ) === 'package:discover';
    }

    private function serverArguments(): array
    {
        $arguments = $_SERVER['argv'] ?? [];

        return is_array($arguments) ? $arguments : [];
    }

    private function commandName(array $argv): ?string
    {
        foreach (array_slice($argv, 1) as $argument) {
            $argument = trim((string) $argument);

            if ($argument === '' || str_starts_with($argument, '-')) {
                continue;
            }

            return $argument;
        }

        return null;
    }
}
