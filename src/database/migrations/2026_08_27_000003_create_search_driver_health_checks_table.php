<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_driver_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('search_driver_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_driver_health_checks');
    }
};
