<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    // The 日本語 / English switch in the header.
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(config('tavnit.locales'))],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return back();
    }
}
