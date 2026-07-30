<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_admin_audit_logs', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->foreignId('mailbox_id')
                ->nullable()
                ->constrained('mailboxes')
                ->nullOnDelete();

            $table->string('event', 100);
            $table->string('status', 20);

            $table->string('subject_type', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->uuid('request_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['mailbox_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_admin_audit_logs');
    }
};
