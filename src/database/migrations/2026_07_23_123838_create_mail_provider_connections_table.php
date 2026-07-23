<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'mail_provider_connections',
            function (Blueprint $table): void {
                $table->id();

                $table->string('name', 120);
                $table->string('provider', 50);
                $table->string('auth_type', 30);

                $table->string('account_identifier', 254)->nullable();
                $table->string('tenant_identifier', 255)->nullable();

                $table->jsonb('configuration')->nullable();

                $table->text('secret_configuration')->nullable();

                $table->jsonb('scopes')->nullable();
                $table->timestampTz('token_expires_at')->nullable();

                $table->boolean('is_active')->default(true);
                $table->string('health_status', 20)->default('unknown');

                $table->timestampTz('last_checked_at')->nullable();
                $table->timestampTz('last_success_at')->nullable();
                $table->timestampTz('last_error_at')->nullable();

                $table->string('last_error_code', 100)->nullable();
                $table->text('last_error_message')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('provider');
                $table->index('auth_type');
                $table->index('is_active');
                $table->index('health_status');
                $table->index('token_expires_at');
                $table->index([
                    'provider',
                    'account_identifier',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_provider_connections');
    }
};
