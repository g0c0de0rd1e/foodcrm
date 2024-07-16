<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Resources\UnitResource;
use App\Repositories\UnitRepository\UnitRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitController extends SellerBaseController
{
    private UnitRepository $unitRepository;

    /**
     * @param UnitRepository $unitRepository
     */
    public function __construct(UnitRepository $unitRepository)
    {
        parent::__construct();
        $this->unitRepository = $unitRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request)
    {
        $units = $this->unitRepository->unitsPaginate($request->all());

        return UnitResource::collection($units);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $unit = $this->unitRepository->unitDetails($id);

        if (empty($unit)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.unit_found'), UnitResource::make($unit));
    }
}
