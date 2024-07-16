<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BlogResource;
use App\Repositories\BlogRepository\BlogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogController extends RestBaseController
{
    private BlogRepository $blogRepository;

    public function __construct(BlogRepository $blogRepository)
    {
        parent::__construct();

        $this->blogRepository = $blogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request)
    {
        $blogs = $this->blogRepository->blogsPaginate($request->merge(['published_at' => true])->all());

        return BlogResource::collection($blogs);
    }

    /**
     * Find Blog by UUID.
     *
     * @return JsonResponse
     */
    public function show(string $uuid)
    {
        $blog = $this->blogRepository->blogByUUID($uuid);

        if (empty($blog)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), BlogResource::make($blog));
    }

}
