<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('administrator') ? true : null;
        });

        Filament::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): string => Blade::render('
                <div style="
                    padding: 10px; 
                    font-size: 12px; 
                    font-weight: 500; 
                ">
                    <div>
                        &copy; {{ date("Y") }} IT_Support-RSHSL
                    </div>
                    <div>
                        Gudang Umum V.2.2
                    </div>
                    
                </div>
            ')
        );
    }
}
