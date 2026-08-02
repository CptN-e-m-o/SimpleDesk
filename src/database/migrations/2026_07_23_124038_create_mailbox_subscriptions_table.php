<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'mailbox_subscriptions',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('mailbox_channel_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('subscription_type', 50);

                $table
                    ->string('external_subscription_id', 255)
                    ->nullable();

                $table->string('status', 20)->default('active');

                $table->text('cursor')->nullable();

                $table->timestampTz('expires_at')->nullable();
                $table->timestampTz('last_notification_at')->nullable();
                $table->timestampTz('last_renewed_at')->nullable();
                $table->timestampTz('last_error_at')->nullable();

                $table->text('last_error_message')->nullable();

                $table->jsonb('metadata')->nullable();

                $table->timestamps();

                $table->index('mailbox_channel_id');
                $table->index('subscription_type');
                $table->index('status');
                $table->index('expires_at');

                $table->unique(
                    [
                        'mailbox_channel_id',
                        'external_subscription_id',
                    ],
                    'mailbox_subscriptions_external_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_subscriptions');
    }
};
