<?php

namespace App\Providers;

use App\Models\Config as DataConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use App\Http\Resources\ConfigResource;

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
                'app' => url('/'),
                'config' => fn() => new ConfigResource(config('website.all')),
                'website' => fn() => config('website.config'),
                'auth' => fn() => [
                    'user' => Auth::user()
                        ? [
                            'id'    => Auth::id(),
                            'name'  => Auth::user()->name,
                            'email' => Auth::user()->email,
                        ]
                        : null,
                ],
            ]);

            View::share('website', $website);
        }
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
