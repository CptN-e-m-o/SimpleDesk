<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_driver_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('mode')->default('deployment');
            $table->foreignId('active_configuration_id')->nullable()->constrained('queue_driver_configurations')->nullOnDelete();
            $table->boolean('worker_restart_required')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_driver_settings');
    }
};
