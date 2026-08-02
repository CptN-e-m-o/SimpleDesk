<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailbox_channels', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('mailbox_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('provider_connection_id')
                ->nullable()
                ->constrained('mail_provider_connections')
                ->nullOnDelete();

            $table->string('name', 120);

            $table->string('direction', 20);

            $table->string('driver', 50);

            $table->string('auth_type', 30)->default('none');

            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_primary')->default(false);

            $table->smallInteger('failover_order')->default(100);

            $table->jsonb('configuration')->nullable();

            $table->text('secret_configuration')->nullable();

            $table->string('health_status', 20)->default('unknown');

            $table->timestampTz('last_checked_at')->nullable();
            $table->timestampTz('last_success_at')->nullable();
            $table->timestampTz('last_activity_at')->nullable();
            $table->timestampTz('last_error_at')->nullable();

            $table->string('last_error_code', 100)->nullable();
            $table->text('last_error_message')->nullable();

            $table->timestamps();

            $table->index('mailbox_id');
            $table->index('provider_connection_id');
            $table->index('direction');
            $table->index('driver');
            $table->index('is_enabled');
            $table->index('is_primary');
            $table->index('health_status');

            $table->index([
                'mailbox_id',
                'direction',
                'is_enabled',
            ]);

            $table->index([
                'mailbox_id',
                'direction',
                'failover_order',
            ]);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                <<<'SQL'
                CREATE UNIQUE INDEX mailbox_channels_one_primary_per_direction_unique
                ON mailbox_channels (mailbox_id, direction)
                WHERE is_primary = TRUE
                SQL
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_channels');
    }
};
