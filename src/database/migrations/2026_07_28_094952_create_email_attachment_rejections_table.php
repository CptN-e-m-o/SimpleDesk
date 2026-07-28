<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_attachment_rejections',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('email_message_id')
                    ->constrained('email_messages')
                    ->cascadeOnDelete();

                $table
                    ->unsignedSmallInteger('position')
                    ->default(0);

                $table
                    ->string('external_id', 512)
                    ->nullable();

                $table
                    ->char('deduplication_key', 64);

                $table->string('file_name', 255);
                $table->string('mime_type', 255);

                $table
                    ->unsignedBigInteger('reported_size')
                    ->nullable();

                $table
                    ->string('content_id', 512)
                    ->nullable();

                $table
                    ->boolean('is_inline')
                    ->default(false);

                $table->string('reason_code', 100);
                $table->text('reason_message');

                $table
                    ->jsonb('metadata')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'email_message_id',
                        'deduplication_key',
                    ],
                    'email_attachment_rejections_dedup_unique'
                );

                $table->index('email_message_id');
                $table->index('reason_code');
                $table->index('external_id');
                $table->index('content_id');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_attachment_rejections'
        );
    }
};
