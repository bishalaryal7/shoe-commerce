<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companion_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('owner')->index();
            $table->string('text', 120);
            $table->date('due_date')->nullable();
            $table->boolean('done')->default(false);
            $table->timestamps();
        });
        Schema::create('companion_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('owner')->unique();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companion_notes');
        Schema::dropIfExists('companion_tasks');
    }
};
