<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('email_message_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->string('external_id', 512)->nullable();

            $table->char('deduplication_key', 64);

            $table->string('file_name', 255);
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size');

            $table->string('disk', 100);
            $table->text('path');

            $table->char('checksum_sha256', 64);

            $table->string('content_id', 512)->nullable();
            $table->boolean('is_inline')->default(false);

            $table->string('scan_status', 30)
                ->default('not_scanned');

            $table->timestampTz('scanned_at')->nullable();
            $table->timestampTz('quarantined_at')->nullable();

            $table->jsonb('scan_result')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'email_message_id',
                    'deduplication_key',
                ],
                'email_attachments_message_dedup_unique'
            );

            $table->index('email_message_id');
            $table->index('external_id');
            $table->index('content_id');
            $table->index('is_inline');
            $table->index('scan_status');
            $table->index('checksum_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
