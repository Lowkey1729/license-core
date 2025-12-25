<?php

namespace App\Concerns;

use App\DTOs\Responses\FailureResponse;
use Illuminate\Contracts\Support\Responsable;

trait ExceptionResponseTrait
{
    public function render(): Responsable
    {
        return new FailureResponse(
            message: $this->message,
            httpStatusCode: $this->code == 0 ? 400 : $this->code,
        );
    }
}
