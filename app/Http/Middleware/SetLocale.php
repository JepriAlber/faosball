<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolusi locale aktif untuk request ini, prioritas:
     * 1. User login DAN users.locale terisi
     * 2. session('locale') (guest, atau user yang belum pernah set locale)
     * 3. config('app.locale') default ("id")
     *
     * Validasi whitelist di sini juga -- BUKAN cuma percaya kolom
     * users.locale/session apa adanya, jaga-jaga data lama/corrupt.
     * Lihat issue7.md Bagian 4.2.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = auth()->user()?->locale
            ?? session('locale')
            ?? config('app.locale');

        if (!array_key_exists($locale, config('app.supported_locales'))) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
