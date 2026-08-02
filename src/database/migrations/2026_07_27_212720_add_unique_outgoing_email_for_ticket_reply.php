<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            <<<'SQL'
                CREATE UNIQUE INDEX email_messages_outgoing_reply_unique
                ON email_messages (ticket_reply_id)
                WHERE direction = 'outgoing'
                  AND ticket_reply_id IS NOT NULL
            SQL
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS email_messages_outgoing_reply_unique'
        );
    }
};
