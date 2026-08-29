<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_driver_health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cache_driver_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('message');
            $table->json('details')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_driver_health_checks');
    }
};
