<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Автофиниш аукциона не имеет администратора — generated_by может быть пустым.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE auction_protocols ALTER COLUMN generated_by DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE auction_protocols ALTER COLUMN generated_by SET NOT NULL');
    }
};
