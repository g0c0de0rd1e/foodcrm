<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ExtraGroupResource;
use App\Http\Resources\ExtraValueResource;
use App\Repositories\ExtraRepository\ExtraGroupRepository;
use App\Repositories\ExtraRepository\ExtraValueRepository;

class ExtraController extends SellerBaseController
{
    private ExtraGroupRepository $extraGroup;
    private ExtraValueRepository $extraValue;

    public function __construct(ExtraGroupRepository $extraGroup, ExtraValueRepository $extraValue)
    {
        parent::__construct();
        $this->extraGroup = $extraGroup;
        $this->extraValue = $extraValue;
    }

    public function extraGroupList(FilterParamsRequest $request)
    {
        $extraGroups = $this->extraGroup->extraGroupList($request->all());

        return $this->successResponse(
            __('web.extra_group_list'),
            ExtraGroupResource::collection($extraGroups)
        );
    }

    public function extraGroupDetails(int $id)
    {
        $extra = $this->extraGroup->extraGroupDetails($id);

        if (!$extra) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            trans('web.extra_found', [], request('lang')),
            ExtraGroupResource::make($extra)
        );
    }

    public function extraValueList(int $groupId)
    {
        $extraValues = $this->extraValue->extraValueList(true, $groupId);

        return $this->successResponse(__('web.extra_values_list'), ExtraValueResource::collection($extraValues));
    }

    public function extraValueDetails(int $id)
    {
        $extraValue = $this->extraValue->extraValueDetails($id);

        if (!$extraValue) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.extra_value_found'), ExtraValueResource::make($extraValue));
    }
}
