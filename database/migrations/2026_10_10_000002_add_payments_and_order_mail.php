<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('payment_method')->default('request');
            $t->string('payment_status')->default('unpaid')->index();
            $t->unsignedBigInteger('shipping_amount')->default(0);
            $t->unsignedBigInteger('tax_amount')->default(0);
            $t->unsignedBigInteger('total')->default(0);
            $t->timestamp('paid_at')->nullable();
        });
        DB::table('orders')->update(['total' => DB::raw('subtotal')]);
        Schema::create('payment_attempts', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('provider');
            $t->string('provider_id')->nullable();
            $t->string('transaction_id')->nullable();
            $t->unsignedBigInteger('amount');
            $t->string('currency', 3);
            $t->string('status')->default('initializing')->index();
            $t->text('payment_url')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->boolean('sandbox')->default(false);
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'provider_id']);
            $t->unique(['provider', 'transaction_id']);
        });
        Schema::create('order_emails', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('event');
            $t->json('payload');
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->unique(['order_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_emails');
        Schema::dropIfExists('payment_attempts');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['payment_method', 'payment_status', 'shipping_amount', 'tax_amount', 'total', 'paid_at']));
    }
};
