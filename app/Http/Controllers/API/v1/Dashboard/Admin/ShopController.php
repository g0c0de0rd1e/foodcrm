<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\ShopExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Shop\ImageDeleteRequest;
use App\Http\Requests\Shop\StoreRequest;
use App\Http\Requests\Shop\ShopStatusChangeRequest;
use App\Http\Resources\ShopResource;
use App\Imports\ShopImport;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\ShopRepository\AdminShopRepository;
use App\Services\Interfaces\ShopServiceInterface;
use App\Services\ShopServices\ShopActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ShopController extends AdminBaseController
{
    public function __construct(private ShopServiceInterface $service, private AdminShopRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $shops = $this->repository->shopsList($request->all());

        return $this->successResponse(__('web.shop_list'), ShopResource::collection($shops));
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $shops = $this->repository->shopsPaginate($request->all());

        return ShopResource::collection($shops);
    }

    /**
     * Shop a newly created.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $seller = User::find($request->input('user_id'));

        if ($seller?->hasRole('admin')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_207]);
        }

        $shop = Shop::where('user_id', $request->input('user_id'))->first();

        if (!empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_206]);
        }

        $result = $this->service->create($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            ShopResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $this->repository->shopDetails($uuid);

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $shop->loadMissing('translations');

        return $this->successResponse(__('web.shop_found'), ShopResource::make($shop));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(StoreRequest $request, string $uuid): JsonResponse
    {
        $result = $this->service->update($uuid, $request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            ShopResource::make(data_get($result, 'data'))
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
    public function setWorkingStatus(): JsonResponse
    {
        (new ShopActivityService)->changeOpenStatus(auth('sanctum')->user()?->shop?->uuid);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR),
            ShopResource::make(auth('sanctum')->user()?->shop)
        );
    }

    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    public function truncate(): JsonResponse
    {
        $this->service->truncate();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    public function restoreAll(): JsonResponse
    {
        $this->service->restoreAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * Search shop Model from database.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function shopsSearch(Request $request): AnonymousResourceCollection
    {
        $categories = $this->repository->shopsSearch($request->all());

        return ShopResource::collection($categories);
    }

    /**
     * Remove Model image from storage.
     *
     * @param ImageDeleteRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(ImageDeleteRequest $request, string $uuid): JsonResponse
    {
        $result = $this->service->imageDelete($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.image_has_been_successfully_delete'), data_get($result, 'shop'));
    }

    /**
     * Change Shop Status.
     *
     * @param string $uuid
     * @param ShopStatusChangeRequest $request
     * @return JsonResponse
     */
    public function statusChange(string $uuid, ShopStatusChangeRequest $request): JsonResponse
    {
        $result = (new ShopActivityService)->changeStatus($uuid, $request->input('status'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.shop_status_change'), []);
    }

    public function fileExport(): JsonResponse
    {
        $fileName = 'export/shops.xls';

        try {
            Excel::store(new ShopExport, $fileName, 'public');

            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable) {
            return $this->errorResponse('Error during export');
        }
    }

    public function fileImport(Request $request): JsonResponse
    {
        try {
            Excel::import(new ShopImport, $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(
                ResponseError::ERROR_508,
                'Excel format incorrect or data invalid ' . $e->getMessage()
            );
        }
    }
}
