<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Repositories\BannerRepository\BannerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BannerController extends RestBaseController
{
    private BannerRepository $repository;

    /**
     * @param BannerRepository $repository
     */
    public function __construct(BannerRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $banners = $this->repository->bannersPaginate($request->merge(['active' => 1])->all());

        return BannerResource::collection($banners);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     *
     * @return JsonResponse
     */
    public function show(int $id, FilterParamsRequest $request): JsonResponse
    {
        $filter = $request->merge(['addon_status' => 'published', 'status' => 'published'])->all();

        $banner = $this->repository->bannerDetails($id, $filter);

        if (empty($banner)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.banner_found'), BannerResource::make($banner));
    }

    public function likedBanner(int $id): JsonResponse
    {
        $banner = Banner::find($id);

        if (empty($banner)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $banner->liked();

        return $this->successResponse(__('web.record_has_been_successfully_updated'), []);
    }
}
