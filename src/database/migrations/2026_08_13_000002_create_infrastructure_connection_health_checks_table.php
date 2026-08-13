<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastructure_connection_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('infrastructure_connection_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->integer('latency_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->string('trigger');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_connection_health_checks');
    }
};
