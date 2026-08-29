<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_driver_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 50);
            $table->foreignId('infrastructure_connection_id')->nullable()->constrained('infrastructure_connections')->restrictOnDelete();
            $table->json('configuration');
            $table->boolean('is_enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_driver_configurations');
    }
};
