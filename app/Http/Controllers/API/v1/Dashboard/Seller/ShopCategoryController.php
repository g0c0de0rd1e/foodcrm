<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\CategoryFilterRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ShopCategory\StoreRequest;
use App\Http\Requests\ShopCategory\UpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepository\CategoryRepository;
use App\Services\ShopCategoryService\ShopCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopCategoryController extends SellerBaseController
{
    private CategoryRepository $categoryRepository;
    private ShopCategoryService $shopCategoryService;

    public function __construct(ShopCategoryService $shopCategoryService, CategoryRepository $categoryRepository)
    {
        parent::__construct();

        $this->shopCategoryService = $shopCategoryService;
        $this->categoryRepository  = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(CategoryFilterRequest $request)
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $shopBrands = $this->categoryRepository->shopCategory($request->merge(['shop_id' => $this->shop->id])->all());

        return CategoryResource::collection($shopBrands);
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
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->shopCategoryService->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_created'), data_get($result, 'data'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $shopBrand = $this->categoryRepository->shopCategoryById($id, $this->shop->id);

        if (!$shopBrand) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.coupon_found'), CategoryResource::make($shopBrand));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $collection = $request->validated();
        $collection['shop_id'] = $this->shop->id;

        $result = $this->shopCategoryService->update($collection, $this->shop);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_has_been_successfully_updated'), data_get($result, 'data'));
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
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $this->shopCategoryService->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }

    /**
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function allCategory(CategoryFilterRequest $request)
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $category = $this->categoryRepository->shopCategoryNonExistPaginate(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return CategoryResource::collection($category);
    }

    /**
     * Display a listing of the resource.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(CategoryFilterRequest $request)
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $shopBrands = $this->categoryRepository->shopCategoryPaginate(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return CategoryResource::collection($shopBrands);
    }

    /**
     * Display a listing of the resource.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function selectPaginate(CategoryFilterRequest $request)
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_204]);
        }

        $shopBrands = $this->categoryRepository->selectPaginate(
            $request->merge(['shop_id' => $this->shop->id, 'type' => Category::MAIN])->all()
        );

        return CategoryResource::collection($shopBrands);
    }
}
