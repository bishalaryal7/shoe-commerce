<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\SocialAccountRepository;
use App\Services\Auth\CompleteSignIn;
use App\Services\Auth\SignInOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider, SignInOptions $options)
    {
        if (! $options->socialAvailable($provider)) {
            return back()->withErrors(['social' => __('This sign-in method is not available yet. Please use email and password.')]);
        }
        $request->session()->put('social_flow', ['provider' => $provider, 'user_id' => $request->user()?->id, 'expires' => now()->addMinutes(10)->timestamp]);

        return Inertia::location(Socialite::driver($provider)->redirect()->getTargetUrl());
    }

    public function callback(Request $request, string $provider, SignInOptions $options, SocialAccountRepository $accounts, CompleteSignIn $signIn)
    {
        $flow = $request->session()->pull('social_flow');
        $destination = $request->user() ? '/settings/connections' : '/login';
        if (! $options->socialAvailable($provider) || ! $flow || $flow['provider'] !== $provider || $flow['expires'] < now()->timestamp || $flow['user_id'] !== $request->user()?->id) {
            $request->session()->forget('state');

            return redirect($destination)->withErrors(['social' => __('Your sign-in session expired. Please start again.')]);
        }
        try {
            // Socialite validates and consumes the session-bound OAuth state.
            $identity = Socialite::driver($provider)->user();
            $user = $accounts->resolve($provider, $identity, $request->user());
        } catch (ValidationException $exception) {
            return redirect($destination)->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            // Provider responses can contain tokens: never log the raw exception.
            return redirect($destination)->withErrors(['social' => __('Social sign-in could not be completed. Please try again or use email and password.')]);
        } finally {
            $request->session()->forget('state');
        }

        return $flow['user_id']
            ? redirect($destination)->with('success', __('Your social account is connected.'))
            : $signIn->handle($request, $user);
    }
}
