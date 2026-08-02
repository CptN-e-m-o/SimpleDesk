<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_message_attempts', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('email_message_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('mailbox_channel_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedSmallInteger('attempt_number');

            $table->string('driver', 50);
            $table->string('status', 20);

            $table->string('external_message_id', 512)->nullable();
            $table->string('internet_message_id', 998)->nullable();

            $table->jsonb('accepted_recipients')->nullable();
            $table->jsonb('rejected_recipients')->nullable();
            $table->jsonb('provider_response')->nullable();

            $table->boolean('retryable')->default(true);
            $table->boolean('failover_allowed')->default(true);

            $table->string('error_class')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'email_message_id',
                'attempt_number',
            ]);

            $table->index('mailbox_channel_id');
            $table->index('driver');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_message_attempts');
    }
};
