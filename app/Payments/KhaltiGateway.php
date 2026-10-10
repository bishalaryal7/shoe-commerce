<?php

namespace App\Payments;

use App\Models\PaymentAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KhaltiGateway implements Gateway
{
    public function available(): bool
    {
        return config('payments.khalti.enabled') && (bool) config('payments.khalti.secret_key');
    }

    public function currency(): string
    {
        return 'NPR';
    }

    private function client()
    {
        if (! $this->available()) {
            throw new RuntimeException('Khalti is not configured.');
        }

        return Http::baseUrl(config('payments.khalti.sandbox') ? config('payments.khalti.sandbox_url') : config('payments.khalti.production_url'))
            ->withHeaders(['Authorization' => 'Key '.config('payments.khalti.secret_key')])->acceptJson()->asJson()->connectTimeout(5)->timeout(20);
    }

    public function initiate(PaymentAttempt $attempt): array
    {
        if ($attempt->currency !== 'NPR' || $attempt->amount < 1000) {
            throw new RuntimeException('Khalti requires an NPR payment of at least Rs. 10.');
        }
        $response = $this->client()->post('/epayment/initiate/', [
            'return_url' => route('payments.return', $attempt->reference), 'website_url' => config('app.url'),
            'amount' => (int) $attempt->amount, 'purchase_order_id' => $attempt->reference,
            'purchase_order_name' => config('app.name').' order',
        ])->throw()->json();
        $url = $response['payment_url'] ?? '';
        if (! is_string($response['pidx'] ?? null) || ! $response['pidx'] || parse_url($url, PHP_URL_SCHEME) !== 'https' || ! in_array(parse_url($url, PHP_URL_HOST), ['test-pay.khalti.com', 'pay.khalti.com'], true)) {
            throw new RuntimeException('Khalti returned an invalid payment session.');
        }

        return ['provider_id' => $response['pidx'], 'payment_url' => $url, 'expires_at' => isset($response['expires_at']) ? Carbon::parse($response['expires_at']) : now()->addMinutes(60)];
    }

    public function lookup(PaymentAttempt $attempt): array
    {
        if ($attempt->sandbox !== (bool) config('payments.khalti.sandbox')) {
            throw new RuntimeException('Payment environment mismatch.');
        }
        $response = $this->client()->post('/epayment/lookup/', ['pidx' => $attempt->provider_id]);
        $data = $response->json();
        if (! $response->successful() && ! ($response->status() === 400 && in_array($data['status'] ?? null, ['Expired', 'User canceled'], true))) {
            $response->throw();
        }

        return ['id' => $data['pidx'] ?? null, 'amount' => $data['total_amount'] ?? null, 'currency' => 'NPR',
            'status' => match ($data['status'] ?? null) {
                'Completed' => 'paid','Expired','User canceled' => 'failed','Refunded' => 'refunded','Partially Refunded' => 'review',default => 'pending'
            },
            'transaction_id' => $data['transaction_id'] ?? null];
    }
}
