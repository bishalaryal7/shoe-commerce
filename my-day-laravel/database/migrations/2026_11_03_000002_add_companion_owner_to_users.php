<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('companion_owner')->nullable()->unique();
            $table->boolean('legacy_imported')->default(false);
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['companion_owner']);
            $table->dropColumn(['companion_owner', 'legacy_imported']);
        });
    }
};
