<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) { $table->boolean('is_admin')->default(false); });
        Schema::create('articles', function (Blueprint $table) {
            $table->id(); $table->string('title', 160); $table->string('summary', 300);
            $table->text('body'); $table->string('category', 50); $table->boolean('published')->default(false);
            $table->boolean('is_sample')->default(false); $table->timestamps();
        });
        Schema::create('reminder_notifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('companion_tasks')->cascadeOnDelete();
            $table->date('due_date'); $table->timestamp('read_at')->nullable(); $table->timestamps();
            $table->unique(['user_id', 'task_id', 'due_date']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('reminder_notifications'); Schema::dropIfExists('articles');
        Schema::table('users', function (Blueprint $table) { $table->dropColumn('is_admin'); });
    }
};
