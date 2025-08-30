<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        App::setLocale('pt_BR');
        setlocale(LC_ALL, 'pt_BR.UTF-8');
        Carbon::setLocale('pt_BR');
    }
}
