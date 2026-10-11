<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function staff()
    {
        return User::where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function byEmail(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
    }

    public function grant(User $user): void
    {
        $user->forceFill(['is_admin' => true])->save();
    }

    public function revoke(User $user): void
    {
        $user->forceFill(['is_admin' => false])->save();
    }
}
