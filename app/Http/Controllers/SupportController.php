<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\User;
use App\Notifications\StoreUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = $user->supportConversations()
            ->withCount('messages')
            ->latest('last_message_at')
            ->paginate(10)
            ->withQueryString();
        $active = null;
        if ($request->filled('conversation')) {
            $active = $user->supportConversations()
                ->with(['messages' => fn ($messages) => $messages->orderBy('id')])
                ->findOrFail($request->integer('conversation'));
        }

        return Inertia::render('shop/Messages', [
            'conversations' => $conversations,
            'activeConversation' => $active,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:4', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $conversation = DB::transaction(function () use ($request, $data) {
            $conversation = SupportConversation::create([
                'user_id' => $request->user()->id,
                'subject' => trim($data['subject']),
                'status' => 'open',
                'last_message_at' => now(),
            ]);
            $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => trim($data['body']),
                'is_staff' => false,
            ]);

            return $conversation;
        });

        User::where('is_admin', true)->each(fn (User $staff) => $staff->notify(new StoreUpdate(
            __('A customer sent a message'),
            __('Open the team workspace to reply to a customer.'),
            '/admin?tab=Messages',
            'support_message',
        )));

        return redirect('/messages?conversation='.$conversation->id)->with('success', __('Your message was sent to EVE Shoes.'));
    }

    public function reply(Request $request, SupportConversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:4000']]);
        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => trim($data['body']),
            'is_staff' => false,
        ]);
        $conversation->update(['status' => 'open', 'last_message_at' => now()]);
        User::where('is_admin', true)->each(fn (User $staff) => $staff->notify(new StoreUpdate(
            __('A customer replied to a message'),
            __('Open the team workspace to continue the conversation.'),
            '/admin?tab=Messages',
            'support_message',
        )));

        return back()->with('success', __('Your reply was sent.'));
    }

    public function close(Request $request, SupportConversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $conversation->update(['status' => 'closed']);

        return back()->with('success', __('This conversation is marked resolved.'));
    }
}
