<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->index(['event_id', 'status', 'priority_score', 'created_at'], 'event_status_priority_created_idx');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE event_registrations MODIFY status ENUM('registered','pending_allocation','cancelled') DEFAULT 'registered'");

            return;
        }

        if ($driver === 'pgsql') {
            $this->expandStatusColumnPostgres();
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE event_registrations MODIFY status ENUM('registered','cancelled') DEFAULT 'registered'");
        } elseif ($driver === 'pgsql') {
            $this->contractStatusColumnPostgres();
        }

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex('event_status_priority_created_idx');
        });
    }

    /**
     * PostgreSQL has no MySQL-style ENUM MODIFY. Normalize to VARCHAR + CHECK (portable, matches Laravel PG enums).
     */
    private function expandStatusColumnPostgres(): void
    {
        DB::statement('ALTER TABLE event_registrations ALTER COLUMN status DROP DEFAULT');

        $constraints = DB::select("
            SELECT c.conname
            FROM pg_constraint c
            JOIN pg_class rel ON rel.oid = c.conrelid
            JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
            WHERE nsp.nspname = ANY (current_schemas(false))
              AND rel.relname = 'event_registrations'
              AND c.contype = 'c'
              AND (
                  c.conname ILIKE '%status%'
                  OR pg_get_constraintdef(c.oid) ILIKE '%status%'
              )
        ");

        foreach ($constraints as $row) {
            $name = $row->conname;
            DB::statement('ALTER TABLE event_registrations DROP CONSTRAINT IF EXISTS '.$this->quotePgIdent($name));
        }

        DB::statement('ALTER TABLE event_registrations ALTER COLUMN status TYPE VARCHAR(32) USING status::text');

        DB::statement("ALTER TABLE event_registrations ADD CONSTRAINT event_registrations_status_check CHECK (status IN ('registered','pending_allocation','cancelled'))");

        DB::statement("ALTER TABLE event_registrations ALTER COLUMN status SET DEFAULT 'registered'");
    }

    private function contractStatusColumnPostgres(): void
    {
        DB::statement("UPDATE event_registrations SET status = 'registered' WHERE status = 'pending_allocation'");

        DB::statement('ALTER TABLE event_registrations ALTER COLUMN status DROP DEFAULT');

        DB::statement('ALTER TABLE event_registrations DROP CONSTRAINT IF EXISTS event_registrations_status_check');

        DB::statement('ALTER TABLE event_registrations ALTER COLUMN status TYPE VARCHAR(32) USING status::text');

        DB::statement("ALTER TABLE event_registrations ADD CONSTRAINT event_registrations_status_check CHECK (status IN ('registered','cancelled'))");

        DB::statement("ALTER TABLE event_registrations ALTER COLUMN status SET DEFAULT 'registered'");
    }

    private function quotePgIdent(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }
};
