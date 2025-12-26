<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Responses\SuccessResponse;
use App\Exceptions\LicenseException;
use App\Requests\ActivateLicenseRequest;
use App\Requests\CheckLicenseRequest;
use App\Requests\DeActivateLicenseRequest;
use App\Services\ProductLicenseService;
use Illuminate\Contracts\Support\Responsable;

class ProductLicenseController extends Controller
{
    public function __construct(
        private readonly ProductLicenseService $productLicenseService
    ) {}

    /**
     * @throws LicenseException
     */
    public function activate(ActivateLicenseRequest $request): Responsable
    {
        $this->productLicenseService->activate($request->validated());

        return new SuccessResponse(message: 'Product license activated successfully.');
    }

    /**
     * @throws LicenseException
     */
    public function deactivate(DeActivateLicenseRequest $request): Responsable
    {
        $this->productLicenseService->deactivate($request->validated());

        return new SuccessResponse(message: 'Product license deactivated successfully.');
    }

    /**
     * @throws LicenseException
     */
    public function check(CheckLicenseRequest $request): Responsable
    {
        $response = $this->productLicenseService->checkStatus($request->validated());

        return new SuccessResponse(message: 'License checked successfully.', data: $response);
    }
}
