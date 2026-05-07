<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Agent Info
            $table->string('email')->unique();
            $table->string('username')->unique();

            $table->string('first_name');
            $table->string('last_name')->nullable();

            $table->string('location')->nullable();

            $table->string('phone_country_iso2', 2)->nullable();
            $table->string('phone_country_code', 8)->nullable();
            $table->string('phone_number')->nullable();
            $table->string('phone_ext')->nullable();

            $table->string('mobile_country_iso2', 2)->nullable();
            $table->string('mobile_country_code', 8)->nullable();
            $table->string('mobile_number')->nullable();

            // Пока agent time zone не реализован
            $table->string('timezone')->default('Europe/Berlin');

            // Agent Signature
            $table->longText('signature')->nullable();

            // Auth
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Account status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('username');
            $table->index(['first_name', 'last_name']);
            $table->index('is_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
