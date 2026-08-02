<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'mailbox_channel_sync_states',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('mailbox_channel_id')
                    ->unique()
                    ->constrained()
                    ->cascadeOnDelete();

                $table->text('cursor')->nullable();
                $table->jsonb('cursor_metadata')->nullable();

                $table->timestampTz('last_sync_started_at')->nullable();
                $table->timestampTz('last_sync_completed_at')->nullable();
                $table->timestampTz('last_sync_failed_at')->nullable();

                $table->unsignedInteger('consecutive_failures')
                    ->default(0);

                $table->unsignedInteger('last_fetched_count')
                    ->default(0);

                $table->unsignedInteger('last_stored_count')
                    ->default(0);

                $table->unsignedInteger('last_duplicate_count')
                    ->default(0);

                $table->unsignedInteger('last_acknowledged_count')
                    ->default(0);

                $table->string('last_error_code', 100)->nullable();
                $table->text('last_error_message')->nullable();

                $table->timestamps();

                $table->index('last_sync_completed_at');
                $table->index('last_sync_failed_at');
                $table->index('consecutive_failures');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_channel_sync_states');
    }
};
