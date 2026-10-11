<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Auth\PhoneSignIn;
use App\Services\Auth\SignInOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class ConnectionsController extends Controller
{
    public function resetPassword(Request $request)
    {
        try {
            $status = Password::sendResetLink(['email' => $request->user()->email]);
        } catch (\Throwable $exception) {
            return back()->withErrors(['password_reset' => __('We could not send a reset link right now. Please try again later.')]);
        }
        if ($status !== Password::ResetLinkSent) {
            return back()->withErrors(['password_reset' => __($status)]);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/forgot-password')->with('status', __($status));
    }

    public function index(Request $request, SignInOptions $options, PhoneSignIn $phone)
    {
        return Inertia::render('settings/Connections', ['signInOptions' => $options->all(), 'connected' => DB::table('social_accounts')->where('user_id', $request->user()->id)->pluck('provider'), 'phone' => $request->user()->phone, 'challenge' => $phone->pending($request)]);
    }

    public function destroy(Request $request, string $provider)
    {
        if ($provider === 'phone') {
            $request->user()->forceFill(['phone' => null, 'phone_verified_at' => null])->save();
            DB::table('phone_challenges')->where('user_id', $request->user()->id)->delete();
            $request->session()->forget('phone_challenge');
        } else {
            DB::table('social_accounts')->where('user_id', $request->user()->id)->where('provider', $provider)->delete();
        }

        return back()->with('success', __('Sign-in method disconnected.'));
    }
}
