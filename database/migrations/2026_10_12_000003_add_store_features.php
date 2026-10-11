<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 16);
            $table->unsignedInteger('amount');
            $table->unsignedBigInteger('minimum_subtotal')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->string('channel')->default('all');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promotion_code')->nullable();
            $table->unsignedBigInteger('discount_amount')->default(0);
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('locale', 2)->default('en');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['variant_id', 'email']);
        });

        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('recipient', 150);
            $table->string('phone', 40);
            $table->text('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn(['tracking_carrier', 'tracking_number', 'shipped_at', 'delivered_at', 'promotion_code', 'discount_amount']);
        });
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('promotions');
    }
};
