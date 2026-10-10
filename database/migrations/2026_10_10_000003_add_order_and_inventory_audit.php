<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->string('offline_payment_reference')->nullable());
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('event');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained()->restrictOnDelete();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->integer('quantity');
            $t->string('reason');
            $t->timestamps();
            $t->unique(['order_id', 'variant_id', 'reason']);
        });
        DB::table('variants')->orderBy('id')->chunkById(250, function ($variants) {
            foreach ($variants as $v) {
                DB::table('inventory_movements')->insert(['variant_id' => $v->id, 'quantity' => $v->stock, 'reason' => 'opening_balance', 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('order_events');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('offline_payment_reference'));
    }
};
