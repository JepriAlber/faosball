<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Ganti preferensi bahasa lalu redirect balik ke halaman asal.
     *
     * $locale divalidasi terhadap whitelist config('app.supported_locales')
     * SEBELUM dipakai -- jangan percaya route parameter mentah-mentah.
     */
    public function switch(Request $request, string $locale)
    {
        abort_unless(
            array_key_exists($locale, config('app.supported_locales')),
            404
        );

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
