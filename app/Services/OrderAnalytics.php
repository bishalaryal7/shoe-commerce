<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OrderAnalytics
{
    public function data(): array
    {
        $currency = config('shop.currency');
        $today = CarbonImmutable::now()->startOfDay();
        $periodStart = $today->subDays(29);
        $paid = fn () => Order::query()->where('currency', $currency)->where('payment_status', 'paid');

        $periodTotals = (clone $paid())
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$periodStart])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue')
            ->first();
        $periodOrders = (int) $periodTotals->orders;

        $dailyRows = (clone $paid())
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$today->subDays(6)])
            ->whereRaw('COALESCE(paid_at, created_at) < ?', [$today->addDay()])
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as day, COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $salesTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $dailyRows) {
            $day = $today->subDays($daysAgo);
            $row = $dailyRows->get($day->toDateString());

            return [
                'date' => $day->toDateString(),
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (int) ($row->revenue ?? 0),
            ];
        })->values();

        $bestSellers = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.currency', $currency)
            ->where('orders.payment_status', 'paid')
            ->whereRaw('COALESCE(orders.paid_at, orders.created_at) >= ?', [$periodStart])
            ->select('order_items.name', 'order_items.size')
            ->selectRaw('SUM(order_items.quantity) as pairs, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('order_items.name', 'order_items.size')
            ->orderByDesc('pairs')
            ->orderBy('order_items.name')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'size' => $row->size,
                'pairs' => (int) $row->pairs,
                'revenue' => (int) $row->revenue,
            ])
            ->values();

        $channels = (clone $paid())
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$periodStart])
            ->select('channel')
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');

        return [
            'currency' => $currency,
            'revenue_today' => (int) (clone $paid())
                ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$today])
                ->whereRaw('COALESCE(paid_at, created_at) < ?', [$today->addDay()])
                ->sum('total'),
            'revenue_30_days' => (int) $periodTotals->revenue,
            'paid_orders_30_days' => $periodOrders,
            'average_order_30_days' => $periodOrders > 0
                ? (int) round((int) $periodTotals->revenue / $periodOrders)
                : 0,
            'sales_trend' => $salesTrend,
            'best_sellers' => $bestSellers,
            'channels' => collect(['retail', 'wholesale'])->map(fn (string $channel) => [
                'channel' => $channel,
                'orders' => (int) ($channels->get($channel)->orders ?? 0),
                'revenue' => (int) ($channels->get($channel)->revenue ?? 0),
            ])->values(),
        ];
    }
}
