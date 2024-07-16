<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Resources\BrandResource;
use App\Repositories\BrandRepository\BrandRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends RestBaseController
{
    private BrandRepository  $brandRepository;
    /**
     * @param BrandRepository $brandRepository
     */
    public function __construct(BrandRepository $brandRepository)
    {
        parent::__construct();

        $this->brandRepository = $brandRepository;
    }

    public function paginate(Request $request)
    {
        $brands = $this->brandRepository->brandsPaginate($request->merge(['active' => 1])->all());

        return BrandResource::collection($brands);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $brand = $this->brandRepository->brandDetails($id);

        if (empty($brand)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), BrandResource::make($brand));
    }
}
