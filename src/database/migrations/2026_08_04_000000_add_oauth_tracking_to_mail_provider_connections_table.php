<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_provider_connections', function (Blueprint $table): void {
            $table->timestampTz('connected_at')->nullable()->after('token_expires_at');
            $table->timestampTz('last_refreshed_at')->nullable()->after('connected_at');
        });
    }

    public function down(): void
    {
        Schema::table('mail_provider_connections', function (Blueprint $table): void {
            $table->dropColumn(['connected_at', 'last_refreshed_at']);
        });
    }
};
