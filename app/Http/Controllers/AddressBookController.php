<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Rules\ContactPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AddressBookController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('settings/Addresses', ['addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->user()->addresses()->count() >= 20) {
            throw ValidationException::withMessages(['label' => __('You can save up to 20 delivery addresses.')]);
        }
        DB::transaction(function () use ($request, $data) {
            if ($data['is_default']) {
                $request->user()->addresses()->update(['is_default' => false]);
            }
            $request->user()->addresses()->create($data);
        });

        return back()->with('success', __('Delivery address saved.'));
    }

    public function update(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $address, $data) {
            if ($data['is_default']) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }
            $address->update($data);
        });

        return back()->with('success', __('Delivery address updated.'));
    }

    public function destroy(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return back()->with('success', __('Delivery address removed.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['label' => ['required', 'string', 'min:2', 'max:60'], 'recipient' => ['required', 'string', 'min:2', 'max:150'], 'phone' => ['required', 'string', 'max:40', new ContactPhone], 'address' => ['required', 'string', 'min:10', 'max:1000'], 'is_default' => ['sometimes', 'boolean']]);
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }
}
