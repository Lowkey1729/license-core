<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Requests\ProvisionLicenseRequest;
use App\Requests\UpdateLicenseStatusRequest;
use App\Services\LicenseService;
use Domain\Shared\DTOs\Responses\SuccessResponse;
use Illuminate\Contracts\Support\Responsable;
use Random\RandomException;

class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseService $licenseService
    ) {}

    /**
     * @throws \Throwable
     * @throws RandomException
     */
    public function store(ProvisionLicenseRequest $request): Responsable
    {
        /** @var Brand $brand */
        $brand = $request->brand;

        $this->licenseService->provision($brand, $request->validated());

        return new SuccessResponse(message: 'License provisioned successfully', httpStatusCode: 201);
    }

    public function update(UpdateLicenseStatusRequest $request, string $id): Responsable
    {
        /** @var Brand $brand */
        $brand = $request->brand;

        $this->licenseService->updateLicenseStatus($brand, $id, $request->validated());

        return new SuccessResponse(message: 'License status updated successfully', httpStatusCode: 200);
    }
}
