<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\ExtraGroup\StoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ExtraGroupResource;
use App\Models\ExtraGroup;
use App\Repositories\ExtraRepository\ExtraGroupRepository;
use App\Services\ExtraGroupService\ExtraGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExtraGroupController extends SellerBaseController
{
    public function __construct(
        private ExtraGroupRepository $repository,
        private ExtraGroupService $service
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $extras = $this->repository->extraGroupList($request->all());

        return ExtraGroupResource::collection($extras);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.extras_list'),
            ExtraGroupResource::make(data_get($result, 'data'))
        );

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $extra = $this->repository->extraGroupDetails($id);

        if (!$extra) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            trans('web.extra_found', [], request('lang')),
            ExtraGroupResource::make($extra->loadMissing([
                'translations',
                'extraValues.group.translation' => fn($q) => $q->where('locale', $this->language)
            ]))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(int $id, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            ExtraGroupResource::make(data_get($result, 'data'))
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
        $hasValues = $this->service->delete($request->input('ids'));

        if ($hasValues > 0) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_504]);
        }

        return $this->successResponse(__('web.record_has_been_successfully_delete'), []);
    }

    /**
     * ExtraGroup type list.
     *
     * @return JsonResponse
     */
    public function typesList(): JsonResponse
    {
        return $this->successResponse(__('web.extra_groups_types'), ExtraGroup::TYPES);
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setActive(int $id): JsonResponse
    {
        $result = $this->service->setActive($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            ExtraGroupResource::make(data_get($result, 'data'))
        );
    }

}
