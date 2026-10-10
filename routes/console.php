<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('shop:admin {email}', function () {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('Register an account first, then run this command with that email.');

        return 1;
    }
    $user->forceFill(['is_admin' => true])->save();
    $this->info('Staff access enabled for '.$user->email);
})->purpose('Grant staff access to an existing registered user');

Schedule::command('shop:reconcile-payments')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('shop:mail-outbox')->everyMinute()->withoutOverlapping();
