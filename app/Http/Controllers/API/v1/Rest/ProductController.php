<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\StocksCalculateRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\UserActivityResource;
use App\Jobs\UserActivityJob;
use App\Models\Category;
use App\Models\Point;
use App\Models\Product;
use App\Models\Shop;
use App\Repositories\CategoryRepository\CategoryRepository;
use App\Repositories\Interfaces\ProductRepoInterface;
use App\Repositories\OrderRepository\OrderRepository;
use App\Repositories\ProductRepository\RestProductRepository;
use App\Repositories\ShopRepository\ShopRepository;
use App\Services\ProductService\ProductReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends RestBaseController
{
    private ProductRepoInterface $productRepository;
    private RestProductRepository $restProductRepository;

    public function __construct(RestProductRepository $restProductRepository, ProductRepoInterface $productRepository)
    {
        parent::__construct();
        $this->middleware('sanctum.check')->only('addProductReview');
        $this->productRepository     = $productRepository;
        $this->restProductRepository = $restProductRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsPaginate(
            $request->merge([
                'rest'          => true,
                'status'        => Product::PUBLISHED,
                'addon_status'  => Product::PUBLISHED,
                'active'        => 1,
                'shop_status'   => 'approved'
            ])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $product = $this->restProductRepository->productByUUID($uuid);

        if (!data_get($product, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        UserActivityJob::dispatchAfterResponse(
            $product->id,
            get_class($product),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR),
            ProductResource::make($product)
        );
    }

    public function productsByShopUuid(string $uuid): JsonResponse|AnonymousResourceCollection
    {
        /** @var Shop $shop */
        $shop = (new ShopRepository)->shopDetails($uuid);

        if (!data_get($shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $products = $this->restProductRepository->productsPaginate(
            ['shop_id' => $shop->id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    public function productsByBrand(int $id): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsPaginate(
            ['brand_id' => $id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    public function productsByCategoryUuid(string $uuid): JsonResponse|AnonymousResourceCollection
    {
        $category = (new CategoryRepository)->categoryByUuid($uuid);

        if (!$category && data_get($category, 'type') !== Category::MAIN) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $products = $this->restProductRepository->productsPaginate(
            ['category_id' => $category->id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    /**
     * Search Model by tag name.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function productsSearch(Request $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->productsSearch(
            $request->merge(['status' => Product::PUBLISHED, 'active' => 1])->all(),
        );

        return ProductResource::collection($products);
    }

    public function mostSoldProducts(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsMostSold($request->all());

        return ProductResource::collection($products);
    }

    /**
     * Search Model by tag name.
     *
     * @param string $uuid
     * @param Request $request
     * @return JsonResponse
     */
    public function addProductReview(string $uuid, Request $request): JsonResponse
    {
        $result = (new ProductReviewService)->addReview($uuid, $request);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(ResponseError::NO_ERROR, []);
    }

    /**
     * Search Model by tag name.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function reviews(string $uuid): JsonResponse
    {
        $result = (new ProductReviewService)->reviews($uuid);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            ReviewResource::collection(data_get($result, 'data'))
        );
    }

    public function discountProducts(Request $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsDiscount(
            $request->merge(['status' => Product::PUBLISHED])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * @param StocksCalculateRequest $request
     * @return JsonResponse
     */
    public function orderStocksCalculate(StocksCalculateRequest $request): JsonResponse
    {
        $result = (new OrderRepository)->orderStocksCalculate($request->validated());

        return $this->successResponse(__('web.products_calculated'), $result);
    }

    /**
     * Get Products by IDs.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function productsByIDs(Request $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->productsByIDs($request->all());

        return ProductResource::collection($products);
    }

    public function checkCashback(Request $request): JsonResponse
    {
        $point = Point::getActualPoint($request->input('amount', 0));

        return $this->successResponse(__('web.cashback'), ['price' => $point]);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param FilterParamsRequest $request
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function productsPaginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsPaginate($request->all());

        return ProductResource::collection($products);
    }

    public function history(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $history = $this->productRepository->history($request->merge(['user_id' => auth('sanctum')->id()])->all());

        return UserActivityResource::collection($history);
    }
}
