<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Shop\StoreRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Repositories\Interfaces\ShopRepoInterface;
use App\Services\Interfaces\ShopServiceInterface;
use App\Services\ShopServices\ShopActivityService;
use Illuminate\Http\JsonResponse;

class ShopController extends SellerBaseController
{

    private ShopRepoInterface $shopRepository;
    private ShopServiceInterface $shopService;

    public function __construct(ShopRepoInterface $shopRepository, ShopServiceInterface $shopService)
    {
        parent::__construct();

        $this->shopRepository = $shopRepository;
        $this->shopService = $shopService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse|
     */
    public function shopCreate(StoreRequest $request): JsonResponse
    {
        $result = $this->shopService->create($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        auth('sanctum')->user()?->invitations()->delete();

        return $this->successResponse(
            __('web.record_successfully_created'),
            ShopResource::make(data_get($result, 'data'))
        );

    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function shopShow(): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        /** @var Shop $shop */
        $shop = $this->shopRepository->shopDetails($this->shop->uuid);

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR),
            ShopResource::make($shop->loadMissing('translations', 'seller.wallet'))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function shopUpdate(StoreRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $result = $this->shopService->update($this->shop->uuid, $request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_updated'),
            ShopResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @return JsonResponse
     */
    public function setWorkingStatus(): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        (new ShopActivityService)->changeOpenStatus($this->shop->uuid);

        return $this->successResponse(
            __('web.record_successfully_updated'),
            ShopResource::make($this->shop)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        //
    }

}
