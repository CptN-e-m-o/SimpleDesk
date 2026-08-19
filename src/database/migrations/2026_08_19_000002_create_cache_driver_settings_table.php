<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cache_driver_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary(); $table->string('mode', 20);
            $table->foreignId('active_configuration_id')->nullable()->constrained('cache_driver_configurations')->restrictOnDelete();
            $table->timestamp('activated_at')->nullable(); $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cache_driver_settings'); }
};
