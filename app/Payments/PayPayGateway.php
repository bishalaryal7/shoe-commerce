<?php

namespace App\Payments;

use App\Models\PaymentAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPayGateway implements Gateway
{
    public function available(): bool
    {
        return config('payments.paypay.enabled') && config('payments.paypay.api_key') && config('payments.paypay.api_secret') && config('payments.paypay.merchant_id');
    }

    public function currency(): string
    {
        return 'JPY';
    }

    public function authorization(string $path, string $method, ?string $body, string $nonce, string $epoch): string
    {
        $type = $body === null ? 'empty' : 'application/json';
        $hash = $body === null ? 'empty' : base64_encode(md5($type.$body, true));
        $signature = base64_encode(hash_hmac('sha256', implode("\n", [$path, $method, $nonce, $epoch, $type, $hash]), config('payments.paypay.api_secret'), true));

        return 'hmac OPA-Auth:'.config('payments.paypay.api_key').':'.$signature.':'.$nonce.':'.$epoch.':'.$hash;
    }

    private function request(string $method, string $path, ?array $data = null)
    {
        if (! $this->available()) {
            throw new RuntimeException('PayPay is not configured.');
        }
        $body = $data === null ? null : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $client = Http::baseUrl(config('payments.paypay.sandbox') ? config('payments.paypay.sandbox_url') : config('payments.paypay.production_url'))
            ->connectTimeout(5)->timeout(35)->replaceHeaders([
                'Accept' => 'application/json',
                'Authorization' => $this->authorization($path, $method, $body, bin2hex(random_bytes(4)), (string) time()),
                'X-ASSUME-MERCHANT' => config('payments.paypay.merchant_id'),
            ]);
        if ($body !== null) {
            $client = $client->withBody($body, 'application/json');
        }

        return $client->send($method, $path);
    }

    public function initiate(PaymentAttempt $attempt): array
    {
        if ($attempt->currency !== 'JPY' || $attempt->amount < 1) {
            throw new RuntimeException('PayPay requires a positive JPY amount.');
        }
        $data = $this->request('POST', '/v2/codes', [
            'merchantPaymentId' => $attempt->reference, 'amount' => ['amount' => (int) $attempt->amount, 'currency' => 'JPY'],
            'codeType' => 'ORDER_QR', 'isAuthorization' => false, 'redirectUrl' => route('payments.return', $attempt->reference),
            'redirectType' => 'WEB_LINK', 'orderDescription' => config('app.name').' sandbox checkout',
        ])->throw()->json();
        $url = $data['data']['url'] ?? '';
        $host = parse_url($url, PHP_URL_HOST);
        if (($data['resultInfo']['code'] ?? null) !== 'SUCCESS' || parse_url($url, PHP_URL_SCHEME) !== 'https' || ! is_string($host) || ! ($host === 'paypay.ne.jp' || str_ends_with($host, '.paypay.ne.jp'))) {
            throw new RuntimeException('PayPay returned an invalid checkout session.');
        }

        return ['provider_id' => $attempt->reference, 'payment_url' => $url, 'expires_at' => isset($data['data']['expiryDate']) ? Carbon::createFromTimestamp($data['data']['expiryDate']) : now()->addMinutes(30)];
    }

    public function lookup(PaymentAttempt $attempt): array
    {
        if ($attempt->sandbox !== (bool) config('payments.paypay.sandbox')) {
            throw new RuntimeException('Payment environment mismatch.');
        }
        $response = $this->request('GET', '/v2/codes/payments/'.rawurlencode($attempt->provider_id));
        $json = $response->json();
        if ($response->status() === 400 && ($json['resultInfo']['code'] ?? null) === 'DYNAMIC_QR_PAYMENT_NOT_FOUND') {
            return ['id' => $attempt->provider_id, 'amount' => $attempt->amount, 'currency' => 'JPY', 'status' => 'failed', 'transaction_id' => null];
        }
        $response->throw();
        if (($json['resultInfo']['code'] ?? null) !== 'SUCCESS') {
            throw new RuntimeException('PayPay lookup was not successful.');
        }
        $data = $json['data'] ?? [];

        return ['id' => $data['merchantPaymentId'] ?? null, 'amount' => $data['amount']['amount'] ?? null, 'currency' => $data['amount']['currency'] ?? null,
            'status' => match ($data['status'] ?? null) {
                'COMPLETED' => 'paid','FAILED','CANCELED','EXPIRED' => 'failed','REFUNDED' => 'refunded',default => 'pending'
            },
            'transaction_id' => $data['paymentId'] ?? null];
    }
}
