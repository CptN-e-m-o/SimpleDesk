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

        foreach (['low' => 'low', 'medium' => 'normal', 'high' => 'high', 'urgent' => 'urgent'] as $legacy => $slug) {
            DB::table('tickets')->where('priority', $legacy)->update(['priority_id' => DB::table('ticket_priorities')->where('slug', $slug)->value('id')]);
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
        DB::table('tickets')->update(['priority' => 'medium']);
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_type_id');
            $table->dropConstrainedForeignId('priority_id');
            $table->index('priority');
        });
    }
};
