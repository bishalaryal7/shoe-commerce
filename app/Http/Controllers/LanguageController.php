<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\LanguageRequest;

class LanguageController extends Controller
{
    public function update(LanguageRequest $request)
    {
        $request->session()->put('locale', $request->validated('locale'));

        return back()->withCookie(cookie('locale', $request->validated('locale'), 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
