<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_driver_configurations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('driver');
            $table->foreignId('infrastructure_connection_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('configuration');
            $table->boolean('is_enabled')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_driver_configurations');
    }
};
