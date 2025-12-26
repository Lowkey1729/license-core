<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidLicenseActionException;
use App\Models\Brand;
use App\Requests\FetchLicensesRequest;
use App\Requests\ProvisionLicenseRequest;
use App\Requests\UpdateLicenseStatusRequest;
use App\Services\BrandLicenseService;
use Domain\Shared\DTOs\Responses\SuccessResponse;
use Illuminate\Contracts\Support\Responsable;
use Random\RandomException;

class BrandLicenseController extends Controller
{
    public function __construct(
        private readonly BrandLicenseService $licenseService
    ) {}

    public function index(FetchLicensesRequest $request): Responsable
    {
        /** @var Brand $brand */
        $brand = $request->brand;

        $results = $this->licenseService->fetchLicenses($brand, $request->validated());

        return new SuccessResponse('Licenses fetched successfully', $results);
    }

    /**
     * @throws \Throwable
     * @throws RandomException
     */
    public function store(ProvisionLicenseRequest $request): Responsable
    {
        /** @var Brand $brand */
        $brand = $request->brand;

        $response = $this->licenseService->provision($brand, $request->validated());

        return new SuccessResponse(
            message: 'License provisioned successfully',
            data: $response,
            httpStatusCode: 201
        );
    }

    /**
     * @throws InvalidLicenseActionException
     */
    public function update(UpdateLicenseStatusRequest $request, string $id): Responsable
    {
        /** @var Brand $brand */
        $brand = $request->brand;

        $this->licenseService->updateLicenseStatus($brand, $id, $request->validated());

        return new SuccessResponse(message: 'License status updated successfully', httpStatusCode: 200);
    }
}
