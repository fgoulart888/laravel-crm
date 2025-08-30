<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1) HTTPS (mantém o que já fizemos)
        if (env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // 2) Locale PT-BR para textos e datas
        App::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR.UTF-8');
        Carbon::setLocale('pt_BR');

        // 3) Formato padrão ao exibir datas (dd/mm/aaaa)
        Carbon::setToStringFormat('d/m/Y');

        // (Opcional) Formato ao serializar datas para JSON
        Carbon::serializeUsing(fn (Carbon $c) => $c->format('d/m/Y H:i:s'));
    }
}
