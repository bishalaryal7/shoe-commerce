<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('merchant_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wholesale_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('accent', 7)->default('#2c4540');
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('merchant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_store_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('price');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['merchant_store_id', 'slug']);
        });
        Schema::create('merchant_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 40);
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();
            $table->unique(['merchant_product_id', 'size']);
        });
        Schema::create('merchant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('reference')->unique();
            $table->uuid('checkout_token')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40);
            $table->text('address');
            $table->unsignedBigInteger('total');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('merchant_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('size', 40);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->timestamps();
        });
        Schema::create('merchant_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 150);
            $table->timestamp('last_message_at');
            $table->timestamps();
        });
        Schema::create('merchant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['merchant_messages', 'merchant_conversations', 'merchant_order_items', 'merchant_orders', 'merchant_variants', 'merchant_products', 'merchant_stores', 'product_photos'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
