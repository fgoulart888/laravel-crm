<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Confia em todos os proxies (Railway / LB)
     */
    protected $proxies = '*';

    /**
     * Considera todos os cabeçalhos X-Forwarded
     * (proto/host/port/ip) vindos do proxy.
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
