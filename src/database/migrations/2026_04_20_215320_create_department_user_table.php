<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('department_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_manager')->default(false);

            $table->timestamps();

            $table->unique(['department_id', 'user_id']);
            $table->index(['department_id', 'is_manager']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
