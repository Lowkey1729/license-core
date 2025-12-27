<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Str;
use Symfony\Component\HttpFoundation\Response;

class RequestTrace
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header('X-Request-Id', Str::uuid()->toString());
        Context::add('traceId', $traceId);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $traceId);
        Context::add('traceId', $traceId);

        return $response;
    }

    public function terminate(Request $request, JsonResponse|\Illuminate\Http\Response $response): void
    {

        \App\Models\RequestTrace::query()->create([
            'trace_id' => Context::get('traceId'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'status_code' => $response->status(),
            'latency_ms' => microtime(true) - \LARAVEL_START,
            'request_body' => secureData($request->all()),
            'response_body' => secureData((array) json_decode($response->content())),
        ]);
    }
}
