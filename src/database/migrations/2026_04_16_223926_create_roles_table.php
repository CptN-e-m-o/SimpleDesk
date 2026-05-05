<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('label');
            $table->text('description')->nullable();

            $table->enum('type', ['user', 'agent'])->default('user');

            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
