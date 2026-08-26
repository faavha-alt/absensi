<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogAndFilterAdmsRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = array_filter(array_map('trim', explode(',', (string) config('zkteco-adms.security.allowed_ips', ''))));

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            Log::channel('adms')->warning('ADMS request blocked: IP not allowlisted', [
                'ip' => $request->ip(),
                'sn' => $request->query('SN'),
                'path' => $request->path(),
            ]);

            return response('ERROR', 403);
        }

        Log::channel('adms')->info('ADMS request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'sn' => $request->query('SN'),
            'query' => $request->query(),
            'body_preview' => substr($request->getContent(), 0, 500),
        ]);

        return $next($request);
    }
}
