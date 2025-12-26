<?php

namespace App\Exceptions;

use App\Concerns\ExceptionResponseTrait;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class ProvisionLicenseException extends Exception implements ShouldntReport
{
    use ExceptionResponseTrait;
}
