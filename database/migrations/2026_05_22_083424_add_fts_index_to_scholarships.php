<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Index dibuat manual via tinker/psql karena GIN tidak bisa dalam transaction
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_scholarships_fts');
    }
};