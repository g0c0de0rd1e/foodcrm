<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Bonus\StoreRequest;
use App\Http\Requests\Bonus\UpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Bonus\BonusResource;
use App\Models\Bonus;
use App\Models\Shop;
use App\Models\Stock;
use App\Repositories\BonusRepository\BonusRepository;
use App\Services\BonusService\BonusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BonusController extends SellerBaseController
{
    private BonusService $service;
    private BonusRepository $repository;

    /**
     * @param BonusService $service
     * @param BonusRepository $repository
     */
    public function __construct(BonusService $service, BonusRepository $repository)
    {
        parent::__construct();

        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $bonus = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return BonusResource::collection($bonus);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $data = $request->validated();

        $type = data_get($data, 'type');

        $data['shop_id']          = $this->shop->id;
        $data['bonusable_type']   = $type === Bonus::TYPE_SUM ? Shop::class : Stock::class;

        $result = $this->service->create($data);

        if (!data_get($result, 'status')) {
          return  $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_created'), []);
    }

    /**
     * Display the specified resource.
     *
     * @param Bonus $bonus
     * @return JsonResponse
     */
    public function show(Bonus $bonus): JsonResponse
    {
        if (!$this->shop || data_get($bonus, 'shop_id') !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $shopBonus = $this->repository->show($bonus);

        return $this->successResponse(__('web.coupon_found'), BonusResource::make($shopBonus));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Bonus $bonus
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Bonus $bonus, UpdateRequest $request): JsonResponse
    {

        if (!$this->shop || data_get($bonus, 'shop_id') !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $collection = $request->validated();

        $type = data_get($collection, 'type');

        $collection['shop_id']          = $this->shop->id;
        $collection['bonusable_type']   = $type === Bonus::TYPE_SUM ? Shop::class : Stock::class;

        $result = $this->service->update($bonus, $collection);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_has_been_successfully_updated'), []);
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

        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }

    public function statusChange(int $id): JsonResponse
    {
        $bonus = Bonus::find($id);

        if (!$this->shop || data_get($bonus, 'shop_id') !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $result = $this->service->statusChange($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_change'),
            BonusResource::make(data_get($result, 'data'))
        );
    }

}
