<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('timezone', 64);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'deleted_at']);
        });

        Schema::create('work_schedule_intervals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('ends_next_day')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['work_schedule_id', 'day_of_week', 'sort_order']);
        });

        Schema::create('work_schedule_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_schedule_id')->constrained();
            $table->foreignId('user_id')->constrained('users');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'effective_from', 'effective_until'], 'work_schedule_assignments_user_dates_idx');
            $table->index(['work_schedule_id', 'effective_from', 'effective_until'], 'work_schedule_assignments_schedule_dates_idx');
        });

        Schema::create('work_schedule_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_schedule_assignment_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 30);
            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['work_schedule_assignment_id', 'date'], 'work_schedule_exception_assignment_date_unique');
        });

        Schema::create('work_schedule_exception_intervals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_schedule_exception_id')->constrained()->cascadeOnDelete();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('ends_next_day')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['work_schedule_exception_id', 'sort_order'], 'work_schedule_exception_intervals_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_exception_intervals');
        Schema::dropIfExists('work_schedule_exceptions');
        Schema::dropIfExists('work_schedule_assignments');
        Schema::dropIfExists('work_schedule_intervals');
        Schema::dropIfExists('work_schedules');
    }
};
