<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    // context service / tenant service.
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user) {

            if ($user->hasRole('Super Admin')) {
                return true;
            }

        });

        // Berlaku untuk SELURUH halaman index yang memanggil {{ $x->links() }} --
        // lihat resources/views/vendor/pagination/faos.blade.php.
        Paginator::defaultView('vendor.pagination.faos');
    }
}
