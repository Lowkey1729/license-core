<?php

namespace App\DTOs\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

class SuccessResponse extends Data
{
    /**
     * @template TValue
     *
     * @param  array<int|string, mixed>|LengthAwarePaginator<int, TValue>|CursorPaginator<int, TValue>  $data
     * @param  array<string, mixed>  $topLevelData
     */
    public function __construct(
        protected string $message,
        protected array|LengthAwarePaginator|CursorPaginator $data = [],
        protected array $topLevelData = [],
        protected int $httpStatusCode = 200
    ) {}

    public function toResponse($request): JsonResponse|Response
    {
        $var = [
            'status' => 'success',
        ];

        if ($this->message) {
            $var['message'] = $this->message;
        }

        if (count($this->topLevelData) > 0) {
            foreach ($this->topLevelData as $key => $item) {
                $var[$key] = $item;
            }
        }

        $var['data'] = $this->data;

        return response()->json(
            $var,
            $this->httpStatusCode
        );
    }
}
