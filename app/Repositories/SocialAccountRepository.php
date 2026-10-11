<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccountRepository
{
    public function resolve(string $provider, ProviderUser $identity, ?User $linkTo): User
    {
        $id = (string) $identity->getId();
        if ($id === '' || strlen($id) > 255) {
            throw ValidationException::withMessages(['social' => __('We could not verify that social account. Please try again.')]);
        }

        return DB::transaction(function () use ($provider, $identity, $id, $linkTo) {
            if ($linkTo) {
                User::whereKey($linkTo->id)->lockForUpdate()->firstOrFail();
            }
            $account = DB::table('social_accounts')->where('provider', $provider)->where('provider_id', $id)->first();
            if ($account) {
                if ($linkTo && $account->user_id !== $linkTo->id) {
                    throw ValidationException::withMessages(['social' => __('That social account is already connected to another customer.')]);
                }

                return User::findOrFail($account->user_id);
            }
            if ($linkTo && DB::table('social_accounts')->where('user_id', $linkTo->id)->where('provider', $provider)->exists()) {
                throw ValidationException::withMessages(['social' => __('Disconnect your current account before connecting a different one.')]);
            }
            if (! $linkTo) {
                $email = Str::lower(trim((string) $identity->getEmail()));
                if (Validator::make(['email' => $email], ['email' => 'required|email:rfc|max:254'])->fails()) {
                    throw ValidationException::withMessages(['social' => __('Your provider did not share an email address. Register with email, then connect your social account in settings.')]);
                }
                // Email matches never grant access to an existing local account.
                if (User::where('email', $email)->exists()) {
                    throw ValidationException::withMessages(['social' => __('An account already uses this email. Sign in with your existing method, then connect this account in settings.')]);
                }
                $linkTo = User::create(['name' => Str::limit(trim((string) $identity->getName()) ?: Str::before($email, '@'), 150, ''), 'email' => $email, 'password' => Str::random(64)]);
                DB::afterCommit(fn () => event(new Registered($linkTo)));
            }
            DB::table('social_accounts')->insert(['user_id' => $linkTo->id, 'provider' => $provider, 'provider_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

            return $linkTo;
        });
    }
}
