<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_reply_parsing_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->text('pattern');
            $table->string('pattern_type', 20);
            $table->string('content_type', 20);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'content_type', 'display_order']);
            $table->index(['deleted_at', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_reply_parsing_rules');
    }
};
