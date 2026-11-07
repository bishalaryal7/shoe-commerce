<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        // Claim this browser's earlier anonymous entries only when creating an account.
        $previous = $request->session()->get('companion_owner');
        $owner = is_string($previous) && Str::isUuid($previous) && ! User::where('companion_owner', $previous)->exists() ? $previous : (string) Str::uuid();
        $user = new User(['name' => trim($data['name']), 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $user->companion_owner = $owner;
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('allow_legacy_import', true);
        return redirect()->route('home');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'The email or password was not recognized.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        if (! $request->user()->companion_owner) {
            $request->user()->forceFill(['companion_owner' => (string) Str::uuid()])->save();
        }
        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
