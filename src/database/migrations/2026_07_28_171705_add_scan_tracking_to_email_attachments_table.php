<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_attachments', function (Blueprint $table): void {
            $table
                ->timestampTz('scan_started_at')
                ->nullable()
                ->after('scan_status');

            $table
                ->unsignedInteger('scan_attempts')
                ->default(0)
                ->after('scan_started_at');

            $table
                ->string('scan_failure_code', 100)
                ->nullable()
                ->after('scanned_at');

            $table
                ->text('scan_failure_message')
                ->nullable()
                ->after('scan_failure_code');

            $table->index(
                [
                    'scan_status',
                    'scan_started_at',
                ],
                'email_attachments_scan_recovery_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('email_attachments', function (Blueprint $table): void {
            $table->dropIndex(
                'email_attachments_scan_recovery_index'
            );

            $table->dropColumn([
                'scan_started_at',
                'scan_attempts',
                'scan_failure_code',
                'scan_failure_message',
            ]);
        });
    }
};
