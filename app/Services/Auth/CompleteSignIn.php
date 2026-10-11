<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;

class CompleteSignIn
{
    public function handle(Request $request, User $user): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->forget(['login.id', 'login.remember']);
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $user->id, 'login.remember' => false]);
            TwoFactorAuthenticationChallenged::dispatch($user);

            return to_route('two-factor.login');
        }
        Auth::login($user);

        return redirect()->intended('/');
    }
}
