<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_message_quarantines',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('email_message_id')
                    ->unique()
                    ->constrained('email_messages')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('mailbox_id')
                    ->nullable()
                    ->constrained('mailboxes')
                    ->nullOnDelete();

                $table
                    ->foreignId('mailbox_channel_id')
                    ->nullable()
                    ->constrained('mailbox_channels')
                    ->nullOnDelete();

                $table->string('stage');

                $table
                    ->string('reason_code')
                    ->nullable();

                $table
                    ->text('reason_message')
                    ->nullable();

                $table
                    ->string('exception_class')
                    ->nullable();

                $table
                    ->unsignedInteger('attempts')
                    ->default(1);

                $table
                    ->timestampTz('first_quarantined_at');

                $table
                    ->timestampTz('last_quarantined_at');

                $table
                    ->timestampTz('released_at')
                    ->nullable();

                $table
                    ->foreignId('released_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestampTz('resolved_at')
                    ->nullable();

                $table
                    ->string('resolution')
                    ->nullable();

                $table
                    ->json('metadata')
                    ->nullable();

                $table->timestampsTz();

                $table->index([
                    'stage',
                    'resolved_at',
                ]);

                $table->index([
                    'mailbox_id',
                    'resolved_at',
                ]);

                $table->index('reason_code');
                $table->index('last_quarantined_at');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_message_quarantines'
        );
    }
};
