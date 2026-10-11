<?php

namespace App\Services\Auth;

class SignInOptions
{
    public function socialAvailable(string $provider): bool
    {
        return in_array($provider, ['google', 'facebook'], true)
            && filled(config("services.$provider.client_id"))
            && filled(config("services.$provider.client_secret"));
    }

    public function phoneAvailable(): bool
    {
        return config('services.twilio.enabled')
            && filled(config('services.twilio.account_sid'))
            && filled(config('services.twilio.auth_token'))
            && filled(config('services.twilio.verify_service_sid'));
    }

    public function all(): array
    {
        return ['google' => $this->socialAvailable('google'), 'facebook' => $this->socialAvailable('facebook'), 'phone' => $this->phoneAvailable()];
    }
}
