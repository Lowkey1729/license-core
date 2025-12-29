<?php

namespace App\Http\Middleware;

use App\DTOs\Responses\FailureResponse;
use App\Exceptions\InvalidBrandKeyException;
use App\Models\BrandApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BrandApiKeyAuth
{
    /**
     * @throws InvalidBrandKeyException
     */
    public function handle(Request $request, Closure $next): Response|FailureResponse
    {
        $key = $request->header('X-BRAND-API-KEY');

        if (is_null($key)) {
            Log::warning('X-BRAND-API-KEY is not set');
            throw new InvalidBrandKeyException('Authentication error', 403);
        }

        $hashedKey = hash('sha256', $key);

        $brandApiKey = BrandApiKey::query()->where('api_key', $hashedKey)->first();
        if (! $brandApiKey) {
            Log::warning('BrandApiKey not found');
            throw new InvalidBrandKeyException('Authentication error', 403);
        }

        $brandApiKey->load('brand');

        $request->merge([
            'brand' => $brandApiKey->brand,
        ]);

        return $next($request);
    }
}
