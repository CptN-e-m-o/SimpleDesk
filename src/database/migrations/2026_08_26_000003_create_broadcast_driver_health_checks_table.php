<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_driver_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broadcast_driver_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->unsignedInteger('latency_ms');
            $table->text('message');
            $table->json('details')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_driver_health_checks');
    }
};
