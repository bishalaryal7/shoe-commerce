<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Notifications\StoreUpdate;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    public function reply(Request $request, SupportConversation $conversation)
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:4000']]);
        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => trim($data['body']),
            'is_staff' => true,
        ]);
        $conversation->update(['status' => 'answered', 'last_message_at' => now()]);
        $conversation->user->notify(new StoreUpdate(
            __('EVE Shoes replied to your message'),
            __('Open your messages to read the team’s reply.'),
            '/messages?conversation='.$conversation->id,
            'support_reply',
        ));

        return back()->with('success', __('Your reply was sent to the customer.'));
    }

    public function status(Request $request, SupportConversation $conversation)
    {
        $data = $request->validate(['status' => ['required', 'in:open,answered,closed']]);
        $conversation->update(['status' => $data['status']]);
        if ($data['status'] !== 'open') {
            [$title, $message] = $data['status'] === 'closed'
                ? [__('Your support conversation was resolved'), __('The shop marked your conversation as resolved. You can start a new message any time.')]
                : [__('The shop is following up on your message'), __('Open your messages to continue the conversation with our team.')];
            $conversation->user->notify(new StoreUpdate(
                $title,
                $message,
                '/messages?conversation='.$conversation->id,
                'support_status',
            ));
        }

        return back()->with('success', __('Message status updated.'));
    }
}
