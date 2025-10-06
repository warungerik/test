<?php

namespace App\Providers;

use App\Models\Config as DataConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

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
        $config = DataConfig::first();
        if ($config) {

            $website = json_decode($config->website);

            Config::set('website.config', $website);
            Config::set('website.all', $config);
            Inertia::share([
                'app' => str_replace('https://admin.', 'https://', url('/')),
                'config' => $config,
                'website' => $website,
                'auth' => function () {
                    return [
                        'user' => auth()->user()
                    ];
                }
            ]);
            View::share('website', $website);
        }
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
