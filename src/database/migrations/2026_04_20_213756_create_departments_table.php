<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('type')->default('public');

            $table->string('business_hours')->nullable();
            $table->string('outgoing_email')->nullable();

            $table->foreignId('department_status_id')
                ->nullable()
                ->constrained('department_statuses')
                ->nullOnDelete();

            $table->longText('signature')->nullable();

            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('type');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
