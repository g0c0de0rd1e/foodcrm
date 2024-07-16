<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\CategoryExport;
use App\Helpers\ResponseError;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryFilterRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\OrderChartRequest;
use App\Http\Resources\CategoryResource;
use App\Imports\CategoryImport;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepoInterface;
use App\Services\CategoryServices\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CategoryController extends AdminBaseController
{
    private CategoryService $categoryService;
    private CategoryRepoInterface $categoryRepository;

    public function __construct(CategoryService $categoryService, CategoryRepoInterface $categoryRepository)
    {
        parent::__construct();
        $this->categoryRepository = $categoryRepository;
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse
     */
    public function index(CategoryFilterRequest $request): JsonResponse
    {
        $categories = $this->categoryRepository->parentCategories($request->all());

        return $this->successResponse(__('web.categories_list'), CategoryResource::collection($categories));
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->parentCategories($request->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function selectPaginate(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->selectPaginate($request->except(['active']));

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @return JsonResponse
     */
    public function store(CategoryCreateRequest $request): JsonResponse
    {
        $result = $this->categoryService->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_created'), []);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $category = $this->categoryRepository->categoryByUuid($uuid);

        if (!$category) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $category->load([
            'translations',
            'metaTags',
        ]);

        return $this->successResponse(__('web.category_found'), CategoryResource::make($category));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(string $uuid, CategoryCreateRequest $request): JsonResponse
    {
        $result = $this->categoryService->update($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_updated'), []);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->categoryService->delete($request->input('ids', []));

        if (!empty(data_get($result, 'data'))) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_504,
                'message'   => 'Can`t delete record that has children or products.'
            ]);
        }

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }
    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->categoryService->dropAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * @return JsonResponse
     */
    public function truncate(): JsonResponse
    {
        $this->categoryService->truncate();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * @return JsonResponse
     */
    public function restoreAll(): JsonResponse
    {
        $this->categoryService->restoreAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * Remove Model image from storage.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(string $uuid): JsonResponse
    {
        $category = Category::firstWhere('uuid', $uuid);

        if (!$category) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $category->galleries()->where('path', $category->img)->delete();

        $category->update(['img' => null]);

        return $this->successResponse(__('web.image_has_been_successfully_delete'), $category);
    }

    /**
     * Search Model by tag name.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function categoriesSearch(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->categoriesSearch($request->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActive(string $uuid): JsonResponse
    {
        /** @var Category $category */
        $category = $this->categoryRepository->categoryByUuid($uuid);

        if (!$category) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $category->update(['active' => !$category->active]);

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            CategoryResource::make($category)
        );
    }

    public function fileExport(): JsonResponse
    {
        $fileName = 'export/categories.xls';

        try {
            Excel::store(new CategoryExport($this->language), $fileName, 'public');
        } catch (Throwable) {
            return $this->errorResponse('Error during export');
        }

        return $this->successResponse('Successfully exported', [
            'path' => 'public/export',
            'file_name' => $fileName
        ]);
    }

    public function fileImport(Request $request): JsonResponse
    {
        try {
            Excel::import(new CategoryImport($this->language), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable) {
            return $this->errorResponse(
                ResponseError::ERROR_508,
                'Excel format incorrect or data invalid'
            );
        }
    }

    public function reportChart(OrderChartRequest $request): JsonResponse
    {
        try {
            $result = $this->categoryRepository->reportChart($request->all());

            return $this->successResponse('', $result);
        } catch (Throwable $exception) {
            return $this->errorResponse(ResponseError::ERROR_400, $exception->getMessage());
        }
    }
}
