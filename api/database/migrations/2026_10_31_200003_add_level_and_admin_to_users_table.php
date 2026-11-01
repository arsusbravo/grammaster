<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The level a student chose; kept when a level is removed.
            $table->foreignId('level_id')->nullable()->after('password')->constrained()->nullOnDelete();
            // Grants access to the Filament admin panel.
            $table->boolean('is_admin')->default(false)->after('level_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
            $table->dropColumn('is_admin');
        });
    }
};
