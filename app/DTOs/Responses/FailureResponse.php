<?php

namespace App\DTOs\Responses;

use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

class FailureResponse extends Data
{

    public function __construct(
        protected string $message,
        protected int $httpStatusCode
    ) {}

    public function toResponse($request): JsonResponse|Response
    {
        return response()->json(
            [
                'status' => 'failed',
                'message' => $this->message,
            ],
            $this->httpStatusCode
        );
    }
}
