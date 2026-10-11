<?php

namespace App\Services;

use App\Jobs\SendStockAlert;
use App\Mail\BackInStock;
use App\Models\StockAlert;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StockAlerts
{
    public function subscribe(Variant $variant, string $email, ?int $userId): void
    {
        StockAlert::updateOrCreate(
            ['variant_id' => $variant->id, 'email' => mb_strtolower($email)],
            ['user_id' => $userId, 'locale' => in_array(app()->getLocale(), ['en', 'ne'], true) ? app()->getLocale() : 'en', 'notified_at' => null],
        );
    }

    public function restocked(Variant $variant): void
    {
        $alerts = StockAlert::with('variant.product')
            ->where('variant_id', $variant->id)->whereNull('notified_at')->get();
        foreach ($alerts as $alert) {
            DB::afterCommit(function () use ($alert): void {
                try {
                    SendStockAlert::dispatch($alert->id);
                } catch (\Throwable $exception) {
                    Log::warning('Back-in-stock alert remains pending after dispatch failure.', ['alert_id' => $alert->id]);
                }
            });
        }
    }

    public function send(int $alertId): void
    {
        $alert = StockAlert::with('variant.product')->whereNull('notified_at')->find($alertId);
        if (! $alert || ! $alert->variant?->product) {
            return;
        }
        $product = $alert->variant->product;
        $name = $alert->locale === 'ne' ? ($product->name_ne ?: $product->name) : $product->name;
        Mail::to($alert->email)->send(new BackInStock($name, $alert->variant->size, $alert->variant->color, route('products.show', $product->slug), $alert->locale));
        $alert->update(['notified_at' => now()]);
    }
}
