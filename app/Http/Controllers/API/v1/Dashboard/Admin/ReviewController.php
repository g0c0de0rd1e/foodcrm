<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Review\PaginateRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Repositories\ReviewRepository\ReviewRepository;
use App\Services\ReviewService\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends AdminBaseController
{
    private ReviewService $service;
    private ReviewRepository $repository;

    /**
     * @param ReviewService $service
     * @param ReviewRepository $repository
     */
    public function __construct(ReviewService $service, ReviewRepository $repository)
    {
        parent::__construct();
        $this->service      = $service;
        $this->repository   = $repository;
    }

    /**
     * @param PaginateRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(PaginateRequest $request): AnonymousResourceCollection
    {
        return ReviewResource::collection($this->repository->paginate($request->all()));
    }

    /**
     * @param Review $review
     * @return JsonResponse
     */
    public function show(Review $review): JsonResponse
    {
        $review = $this->repository->show($review);

        if (empty($review)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.review_found'), ReviewResource::make($review));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->destroy(is_array($request->input('ids')) ? $request->input('ids') : []);

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
