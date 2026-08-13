<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->string('availability', 20);
            $table->string('routing_eligibility', 20);
            $table->string('icon', 50);
            $table->string('color', 7);
            $table->unsignedInteger('default_duration_minutes')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_selectable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'is_selectable']);
            $table->index(['availability', 'routing_eligibility']);
        });

        Schema::create('agent_status_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_status_id')->constrained('agent_statuses')->restrictOnDelete();
            $table->string('scope', 20)->default('global');
            $table->string('channel', 30)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('revert_to_status_id')->nullable()->constrained('agent_statuses')->nullOnDelete();
            $table->string('note', 1000)->nullable();
            $table->string('source', 20);
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason', 30)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('agent_status_id');
            $table->index('ended_at');
            $table->index('expires_at');
            $table->index(['user_id', 'scope', 'channel']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_status_periods');
        Schema::dropIfExists('agent_statuses');
    }
};
