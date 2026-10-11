<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;

class TwilioVerify
{
    private function client()
    {
        return Http::asForm()->acceptJson()->withBasicAuth(config('services.twilio.account_sid'), config('services.twilio.auth_token'))
            ->connectTimeout(5)->timeout(15)->baseUrl('https://verify.twilio.com/v2/Services/'.config('services.twilio.verify_service_sid'));
    }

    public function send(string $phone): string
    {
        $result = $this->client()->post('/Verifications', ['To' => $phone, 'Channel' => 'sms'])->throw()->json();
        if (($result['status'] ?? null) !== 'pending' || ! preg_match('/^VE[0-9a-f]{32}$/i', $result['sid'] ?? '')) {
            throw new \RuntimeException('SMS provider did not create a verification.');
        }

        return $result['sid'];
    }

    public function check(string $sid, string $phone, string $code): bool
    {
        $response = $this->client()->post('/VerificationCheck', ['VerificationSid' => $sid, 'Code' => $code]);
        if (in_array($response->status(), [400, 404, 429], true)) {
            return false;
        }
        $result = $response->throw()->json();

        return ($result['status'] ?? '') === 'approved'
            && ($result['sid'] ?? '') === $sid
            && ($result['to'] ?? '') === $phone;
    }
}
