<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\GrantStaffRequest;
use App\Models\User;
use App\Services\StaffAccess;
use Illuminate\Http\Request;

class StaffAccessController extends Controller
{
    public function store(GrantStaffRequest $request, StaffAccess $access)
    {
        $access->grant($request->validated('email'), $request->user()->id);

        return back()->with('success', __('Staff access granted. They can sign in with their own account.'));
    }

    public function destroy(Request $request, User $user, StaffAccess $access)
    {
        $access->revoke($user, $request->user()->id);

        return back()->with('success', __('Staff access removed.'));
    }
}
