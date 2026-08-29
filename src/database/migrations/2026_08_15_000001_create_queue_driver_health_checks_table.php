<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_driver_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('queue_driver_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->integer('latency_ms')->nullable();
            $table->text('message');
            $table->json('details')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_driver_health_checks');
    }
};
