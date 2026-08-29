<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7);
            $table->string('visibility', 16);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'visibility', 'sort_order']);
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('visibility', 16);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'visibility', 'sort_order']);
        });

        DB::statement('CREATE UNIQUE INDEX ticket_priorities_active_name_unique ON ticket_priorities (LOWER(name)) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX ticket_priorities_single_default ON ticket_priorities (is_default) WHERE is_default = true AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX ticket_types_active_name_unique ON ticket_types (LOWER(name)) WHERE deleted_at IS NULL');

        $now = now();
        DB::table('ticket_priorities')->insert([
            ['name' => 'Low', 'slug' => 'low', 'color' => '#2563EB', 'visibility' => 'public', 'sort_order' => 10, 'is_default' => false, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Normal', 'slug' => 'normal', 'color' => '#16A34A', 'visibility' => 'public', 'sort_order' => 20, 'is_default' => true, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'High', 'slug' => 'high', 'color' => '#EA580C', 'visibility' => 'public', 'sort_order' => 30, 'is_default' => false, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Urgent', 'slug' => 'urgent', 'color' => '#DC2626', 'visibility' => 'public', 'sort_order' => 40, 'is_default' => false, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('ticket_types')->insert(array_map(fn (array $type) => [...$type, 'visibility' => 'public', 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now], [
            ['name' => 'Incident', 'slug' => 'incident', 'sort_order' => 10],
            ['name' => 'Service Request', 'slug' => 'service-request', 'sort_order' => 20],
            ['name' => 'Problem', 'slug' => 'problem', 'sort_order' => 30],
            ['name' => 'Question', 'slug' => 'question', 'sort_order' => 40],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('ticket_priorities');
    }
};
