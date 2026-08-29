<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('queue_driver_configurations')
            ->select([
                'id',
                'driver',
                'configuration',
            ])
            ->orderBy('id')
            ->get();

        /*
         * Validate every legacy Redis reference before
         * changing the schema or mutating stored data.
         */
        foreach ($rows as $row) {
            if ($row->driver !== 'redis') {
                continue;
            }

            $configuration = $this->decodeConfiguration(
                $row->configuration,
            );

            $connectionId =
                $configuration[
                'infrastructure_connection_id'
                ] ?? null;

            if (
                ! is_numeric($connectionId)
                || (int) $connectionId <= 0
            ) {
                throw new RuntimeException(
                    "Redis queue configuration [{$row->id}] does not contain a valid infrastructure connection reference.",
                );
            }

            $exists = DB::table('infrastructure_connections')
                ->where(
                    'id',
                    (int) $connectionId,
                )
                ->exists();

            if (! $exists) {
                throw new RuntimeException(
                    "Redis queue configuration [{$row->id}] references missing infrastructure connection [{$connectionId}].",
                );
            }
        }

        Schema::table(
            'queue_driver_configurations',
            function (Blueprint $table): void {
                $table
                    ->foreignId(
                        'infrastructure_connection_id',
                    )
                    ->nullable()
                    ->constrained(
                        'infrastructure_connections',
                    )
                    ->restrictOnDelete();
            },
        );

        foreach ($rows as $row) {
            $configuration = $this->decodeConfiguration(
                $row->configuration,
            );

            $connectionId = null;

            if ($row->driver === 'redis') {
                $connectionId = (int) $configuration[
                'infrastructure_connection_id'
                ];
            }

            unset(
                $configuration[
                'infrastructure_connection_id'
                ],
            );

            DB::table('queue_driver_configurations')
                ->where(
                    'id',
                    $row->id,
                )
                ->update([
                    'infrastructure_connection_id' => $connectionId,

                    'configuration' => $this->encodeConfiguration(
                        $configuration,
                    ),
                ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('queue_driver_configurations')
            ->select([
                'id',
                'driver',
                'configuration',
                'infrastructure_connection_id',
            ])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $configuration = $this->decodeConfiguration(
                $row->configuration,
            );

            if (
                $row->driver === 'redis'
                && $row->infrastructure_connection_id !== null
            ) {
                $configuration[
                'infrastructure_connection_id'
                ] = (int) $row
                    ->infrastructure_connection_id;
            }

            DB::table('queue_driver_configurations')
                ->where(
                    'id',
                    $row->id,
                )
                ->update([
                    'configuration' => $this->encodeConfiguration(
                        $configuration,
                    ),
                ]);
        }

        Schema::table(
            'queue_driver_configurations',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'infrastructure_connection_id',
                );
            },
        );
    }

    private function decodeConfiguration(
        mixed $value,
    ): array {
        if (
            $value === null
            || $value === ''
        ) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        try {
            $decoded = json_decode(
                (string) $value,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Queue driver configuration contains invalid JSON.',
                previous: $exception,
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'Queue driver configuration JSON must decode to an array.',
            );
        }

        return $decoded;
    }

    private function encodeConfiguration(
        array $configuration,
    ): string {
        try {
            return json_encode(
                $configuration,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Queue driver configuration could not be encoded as JSON.',
                previous: $exception,
            );
        }
    }
};
