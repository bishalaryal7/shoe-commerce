<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StaffAccess
{
    public function __construct(private UserRepository $users) {}

    public function grant(string $email, int $actor): void
    {
        $user = $this->users->byEmail($email);
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'This person must register an account first.']);
        }
        $this->users->grant($user);
        Log::notice('Staff access granted.', ['actor_id' => $actor, 'user_id' => $user->id]);
    }

    public function revoke(User $user, int $actor): void
    {
        if ($user->id === $actor) {
            throw ValidationException::withMessages(['team' => 'You cannot remove your own staff access.']);
        }
        $this->users->revoke($user);
        Log::notice('Staff access revoked.', ['actor_id' => $actor, 'user_id' => $user->id]);
    }
}
