<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('mailbox_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table
                ->foreignId('mailbox_channel_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table
                ->foreignId('ticket_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table
                ->foreignId('ticket_reply_id')
                ->nullable()
                ->constrained('ticket_replies')
                ->nullOnDelete();

            $table->string('direction', 20);
            $table->string('driver', 50);
            $table->string('status', 30);

            $table->string('idempotency_key', 128)->unique();

            $table->string('external_message_id', 512)->nullable();

            $table->string('internet_message_id', 998)->nullable();

            $table
                ->string('in_reply_to_message_id', 998)
                ->nullable();

            $table->jsonb('reference_message_ids')->nullable();

            $table->string('sender_address', 254)->nullable();
            $table->string('sender_name', 255)->nullable();

            $table->jsonb('to_recipients')->nullable();
            $table->jsonb('cc_recipients')->nullable();
            $table->jsonb('bcc_recipients')->nullable();
            $table->jsonb('reply_to_recipients')->nullable();

            $table->text('subject')->nullable();
            $table->longText('text_body')->nullable();
            $table->longText('html_body')->nullable();

            $table->jsonb('headers')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->string('raw_message_disk', 100)->nullable();
            $table->text('raw_message_path')->nullable();

            $table->timestampTz('received_at')->nullable();
            $table->timestampTz('queued_at')->nullable();
            $table->timestampTz('processing_started_at')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('failed_at')->nullable();

            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();

            $table->timestamps();

            $table->index('mailbox_id');
            $table->index('mailbox_channel_id');
            $table->index('ticket_id');
            $table->index('ticket_reply_id');
            $table->index('direction');
            $table->index('driver');
            $table->index('status');
            $table->index('external_message_id');
            $table->index('internet_message_id');
            $table->index('received_at');
            $table->index('sent_at');

            $table->index([
                'mailbox_id',
                'direction',
                'status',
            ]);

            $table->index([
                'mailbox_channel_id',
                'external_message_id',
            ]);

            $table->index([
                'ticket_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
