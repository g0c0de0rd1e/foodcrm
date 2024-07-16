<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Discount\SellerRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use App\Repositories\DiscountRepository\DiscountRepository;
use App\Services\DiscountService\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class DiscountController extends SellerBaseController
{
    private DiscountRepository $discountRepository;
    private DiscountService $discountService;

    /**
     * @param DiscountRepository $discountRepository
     * @param DiscountService $discountService
     */
    public function __construct(DiscountRepository $discountRepository, DiscountService $discountService)
    {
        parent::__construct();
        $this->discountRepository = $discountRepository;
        $this->discountService = $discountService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function paginate(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $discounts = $this->discountRepository->discountsPaginate(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return DiscountResource::collection($discounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->discountService->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            DiscountResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Discount $discount
     * @return JsonResponse
     */
    public function show(Discount $discount): JsonResponse
    {
        if (!$this->shop || $discount->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $discount = $this->discountRepository->discountDetails($discount);

        return $this->successResponse(__('web.discount_found'), DiscountResource::make($discount));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Discount $discount
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(Discount $discount, SellerRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->discountService->update($discount, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            DiscountResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $discounts = Discount::whereIn('id', $request->input('ids', []))
            ->where('shop_id', $this->shop->id)
            ->get();

        foreach ($discounts as $discount) {

            /** @var Discount $discount */

            try {
                $discount->galleries()->delete();
                $discount->products()->sync([]);
            } catch (Throwable $e) {
                $this->error($e);
            }

            $discount->delete();
        }

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }

    public function setActiveStatus($id): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $discount = Discount::firstWhere(['id' => $id, 'shop_id' => $this->shop->id]);

        if (!$discount) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $discount->update(['active' => !$discount->active]);

        return $this->successResponse(__('web.record_active_update'), DiscountResource::make($discount));
    }
}
