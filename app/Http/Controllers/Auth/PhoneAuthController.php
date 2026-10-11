<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPhoneCodeRequest;
use App\Rules\ContactPhone;
use App\Services\Auth\CompleteSignIn;
use App\Services\Auth\PhoneSignIn;
use App\Services\Auth\SignInOptions;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PhoneAuthController extends Controller
{
    public function show(Request $request, PhoneSignIn $phone, SignInOptions $options)
    {
        return Inertia::render('auth/Phone', ['available' => $options->phoneAvailable(), 'challenge' => $phone->pending($request)]);
    }

    public function send(SendPhoneCodeRequest $request, PhoneSignIn $phone)
    {
        $phone->send($request, $request->validated('phone'), $request->user() ? 'link' : 'login');

        return back()->with('success', __('If this number is eligible, a verification code has been sent.'));
    }

    public function verify(Request $request, PhoneSignIn $phone, CompleteSignIn $signIn)
    {
        if (is_string($request->code)) {
            $request->merge(['code' => ContactPhone::normalize($request->code)]);
        }
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{4,10}$/']]);
        $linking = $request->user() !== null;
        try {
            $user = $phone->confirm($request, $data['code'], $linking ? 'link' : 'login');
        } catch (UniqueConstraintViolationException $exception) {
            return back()->withErrors(['code' => __('This phone number cannot be connected to your account.')]);
        }

        return $linking ? back()->with('success', __('Your phone number is verified. You can now use it to sign in.')) : $signIn->handle($request, $user);
    }
}
