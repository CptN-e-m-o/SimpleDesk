<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailboxes', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 120);
            $table->string('email_address', 254);
            $table->string('display_name', 120)->nullable();

            $table
                ->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default_outgoing')->default(false);

            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('department_id');
            $table->index('is_active');
            $table->index('is_default_outgoing');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                <<<'SQL'
                CREATE UNIQUE INDEX mailboxes_email_address_active_unique
                ON mailboxes (LOWER(email_address))
                WHERE deleted_at IS NULL
                SQL
            );

            DB::statement(
                <<<'SQL'
                CREATE UNIQUE INDEX mailboxes_single_default_outgoing_unique
                ON mailboxes (is_default_outgoing)
                WHERE is_default_outgoing = TRUE
                    AND deleted_at IS NULL
                SQL
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mailboxes');
    }
};
