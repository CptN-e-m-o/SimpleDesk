<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('guard')->nullable();

            $table->string('session_id')->nullable()->index();

            $table->string('device_type')->nullable();
            $table->string('device_name')->nullable();

            $table->string('platform')->nullable();
            $table->string('platform_version')->nullable();

            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();

            $table->ipAddress('ip_address')->nullable();

            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamp('logged_in_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();

            $table->boolean('is_current')->default(false);
            $table->boolean('is_successful')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
            $table->index(['user_id', 'last_activity_at']);
            $table->index(['user_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_sessions');
    }
};
