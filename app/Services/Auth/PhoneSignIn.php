<?php

namespace App\Services\Auth;

use App\Models\PhoneChallenge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhoneSignIn
{
    public function __construct(private TwilioVerify $verify, private SignInOptions $options) {}

    public function send(Request $request, string $phone, string $purpose): void
    {
        $this->available();
        foreach ([['minute', 1, 60], ['hour', 5, 3600], ['day', 10, 86400]] as [$window, $limit, $seconds]) {
            $key = 'phone-code:'.$window.':'.hash('sha256', $phone);
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw ValidationException::withMessages(['phone' => __('Please wait before requesting another code.')]);
            }
        }
        foreach ([['minute', 60], ['hour', 3600], ['day', 86400]] as [$window, $seconds]) {
            RateLimiter::hit('phone-code:'.$window.':'.hash('sha256', $phone), $seconds);
        }
        $user = $purpose === 'link' ? $request->user() : User::where('phone', $phone)->whereNotNull('phone_verified_at')->first();
        $sid = null;
        if ($user) {
            try {
                $sid = $this->verify->send($phone);
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages(['phone' => __('We could not send a code right now. Please try again later.')]);
            }
        }
        // Unknown numbers get the same response, without disclosing account membership.
        PhoneChallenge::where('session_hash', hash('sha256', $request->session()->getId()))->whereNull('used_at')->update(['used_at' => now()]);
        $challenge = PhoneChallenge::create(['id' => (string) Str::uuid(), 'user_id' => $user?->id, 'purpose' => $purpose, 'session_hash' => hash('sha256', $request->session()->getId()), 'phone' => $phone, 'provider_sid' => $sid, 'expires_at' => now()->addMinutes(10)]);
        $request->session()->put('phone_challenge', $challenge->id);
    }

    public function pending(Request $request): ?array
    {
        $challenge = $this->challenge($request);

        return $challenge ? ['phone' => '•••• '.substr($challenge->phone, -4), 'expiresAt' => $challenge->expires_at->toIso8601String()] : null;
    }

    public function confirm(Request $request, string $code, string $purpose): User
    {
        $this->available();
        // The lock prevents concurrent successful requests from reusing a code.
        $user = DB::transaction(function () use ($request, $code, $purpose) {
            $challenge = $this->challenge($request, true);
            if (! $challenge || $challenge->purpose !== $purpose || ($purpose === 'link' && $challenge->user_id !== $request->user()?->id) || $challenge->attempts >= 5) {
                return null;
            }
            $challenge->increment('attempts');
            try {
                $valid = $challenge->provider_sid && $this->verify->check($challenge->provider_sid, $challenge->phone, $code);
            } catch (\Throwable $exception) {
                $valid = false;
            }
            if (! $valid) {
                return null;
            }
            $challenge->update(['used_at' => now()]);
            $user = User::whereKey($challenge->user_id)->lockForUpdate()->first();
            if (! $user) {
                return null;
            }
            if ($purpose === 'link') {
                if (User::where('phone', $challenge->phone)->where('id', '!=', $user->id)->exists()) {
                    return null;
                }
                $user->forceFill(['phone' => $challenge->phone, 'phone_verified_at' => now()])->save();
            } elseif ($user->phone !== $challenge->phone || ! $user->phone_verified_at) {
                return null;
            }

            return $user;
        });
        if (! $user) {
            throw ValidationException::withMessages(['code' => __('This code is invalid, expired, or cannot be used for this account. Request a new code and try again.')]);
        }
        $request->session()->forget('phone_challenge');

        return $user;
    }

    private function challenge(Request $request, bool $lock = false): ?PhoneChallenge
    {
        $query = PhoneChallenge::whereKey($request->session()->get('phone_challenge'))->where('session_hash', hash('sha256', $request->session()->getId()))->whereNull('used_at')->where('expires_at', '>', now());

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function available(): void
    {
        if (! $this->options->phoneAvailable()) {
            throw ValidationException::withMessages(['phone' => __('Phone sign-in is not available yet. Please use email and password.')]);
        }
    }
}
