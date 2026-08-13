<?php

namespace App\Services\Admin\System\Queues;

class QueueRuntimeBootstrapPolicy
{
    public function maySkipDatabaseInspectionFailure(
        ?array $argv = null,
    ): bool {
        if (! app()->runningInConsole()) {
            return false;
        }

        $command =
            $this->commandName(
                $argv
                ?? (
                    $_SERVER[
                    'argv'
                    ]
                    ?? []
                ),
            );

        return in_array(
            $command,
            [
                'package:discover',
            ],
            true,
        );
    }

    private function commandName(
        array $argv,
    ): ?string {
        foreach (
            array_slice(
                $argv,
                1,
            ) as $argument
        ) {
            $argument =
                trim(
                    (string) $argument,
                );

            if (
                $argument === ''
                || str_starts_with(
                    $argument,
                    '-',
                )
            ) {
                continue;
            }

            return $argument;
        }

        return null;
    }
}
