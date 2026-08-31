<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('priority_id')->nullable()->after('subject')->constrained('ticket_priorities')->restrictOnDelete();
            $table->foreignId('ticket_type_id')->nullable()->after('priority_id')->constrained('ticket_types')->nullOnDelete();
        });

        foreach (DB::table('ticket_priorities')->get(['id', 'slug']) as $priority) {
            DB::table('tickets')
                ->where('priority', $priority->slug === 'normal' ? 'medium' : $priority->slug)
                ->update(['priority_id' => $priority->id]);
        }

        $defaultId = DB::table('ticket_priorities')->where('is_default', true)->value('id');
        DB::table('tickets')->whereNull('priority_id')->update(['priority_id' => $defaultId]);

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropColumn('priority');
            $table->unsignedBigInteger('priority_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('priority')->nullable();
        });

        DB::table('tickets')
            ->join('ticket_priorities', 'ticket_priorities.id', '=', 'tickets.priority_id')
            ->select(['tickets.id', 'ticket_priorities.slug'])
            ->orderBy('tickets.id')
            ->chunkById(500, function ($tickets) {
                foreach ($tickets as $ticket) {
                    DB::table('tickets')->where('id', $ticket->id)->update([
                        'priority' => $ticket->slug === 'normal' ? 'medium' : $ticket->slug,
                    ]);
                }
            }, 'tickets.id', 'id');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_type_id');
            $table->dropConstrainedForeignId('priority_id');
            $table->index('priority');
        });
    }
};
