<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_webhook_events',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('mailbox_channel_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table
                    ->foreignId('email_message_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->string('provider', 50);
                $table->string('event_type', 100);

                $table
                    ->string('external_event_id', 512)
                    ->nullable();

                $table->string('idempotency_key', 128)->unique();

                $table
                    ->boolean('signature_verified')
                    ->default(false);

                $table->jsonb('payload');

                $table->string('status', 20)->default('pending');
                $table->unsignedSmallInteger('attempts')->default(0);

                $table->timestampTz('received_at');
                $table->timestampTz('processing_started_at')->nullable();
                $table->timestampTz('processed_at')->nullable();
                $table->timestampTz('failed_at')->nullable();

                $table->text('last_error_message')->nullable();

                $table->timestamps();

                $table->index('mailbox_channel_id');
                $table->index('email_message_id');
                $table->index('provider');
                $table->index('event_type');
                $table->index('external_event_id');
                $table->index('status');
                $table->index('received_at');

                $table->index([
                    'provider',
                    'event_type',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('email_webhook_events');
    }
};
