<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Menangani exception dari Service secara terpusat.
     *
     * Exception dicatat ke log, lalu user diarahkan kembali dengan
     * flash message error. Berikan $route untuk redirect ke route
     * tertentu (mis. destroy), atau biarkan null agar kembali ke
     * halaman sebelumnya dengan input yang masih terisi (mis. store/update).
     */
    protected function handleException(
        \Exception $e,
        string $message,
        ?string $route = null,
        array $routeParameters = []
    ): RedirectResponse {

        Log::error($message, [
            'exception' => $e,
        ]);

        $redirect = $route
            ? redirect()->route($route, $routeParameters)
            : back()->withInput();

        return $redirect->with(
            'error',
            $message.': '.$e->getMessage()
        );
    }
}
