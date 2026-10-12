<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('shop/Notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();
        $url = $record->data['url'] ?? '/notifications';

        return redirect(is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : '/notifications');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', __('All notifications have been marked as read.'));
    }
}
