<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('permission_group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('permissions')
                ->nullOnDelete();

            $table->string('key')->unique();
            $table->string('label');

            $table->enum('type', ['user', 'agent'])->default('user');
            $table->enum('ui_type', ['checkbox', 'radio'])->default('checkbox');

            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['type', 'permission_group_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
