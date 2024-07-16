<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ShopGallery\AdminStoreRequest;
use App\Http\Resources\ShopGalleryResource;
use App\Models\ShopGallery;
use App\Repositories\ShopGalleryRepository\ShopGalleryRepository;
use App\Services\ShopGalleryService\ShopGalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopGalleriesController extends AdminBaseController
{
    public function __construct(private ShopGalleryService $service, private ShopGalleryRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $models = ShopGallery::filter($request->all())
            ->with(['galleries'])
            ->orderBy($request->input('column', 'id'), $request->input('sort', 'desc'))
            ->paginate($request->input('perPage', 15));

        return $this->successResponse(
            __('web.coupon_found'),
            ShopGalleryResource::collection($models)
        );
    }

    /**
     * @param ShopGallery $shopGallery
     * @return JsonResponse
     */
    public function show(ShopGallery $shopGallery): JsonResponse
    {
        $model = $this->repository->show($shopGallery);

        return $this->successResponse(
            __('web.coupon_found'),
            ShopGalleryResource::make($model)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            ShopGalleryResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $shopId
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function update(int $shopId, AdminStoreRequest $request): JsonResponse
    {
        $model = ShopGallery::where('shop_id', $shopId)->first();

        if ($model?->shop_id !== $shopId) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $validated = $request->validated();

        $result = $this->service->update($model, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            ShopGalleryResource::make(data_get($result, 'data'))
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
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * @return JsonResponse
     */
    public function truncate(): JsonResponse
    {
        $this->service->truncate();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * @return JsonResponse
     */
    public function restoreAll(): JsonResponse
    {
        $this->service->restoreAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

}
