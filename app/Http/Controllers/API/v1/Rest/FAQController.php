<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\FAQResource;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\TermCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FAQController extends RestBaseController
{
    /**
     * Display a listing of the FAQ.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $faqs = Faq::with([
                'translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->where('active', 1)
            ->when($request->input('deleted_at'), fn($q) => $q->onlyTrashed())
            ->orderBy($request->input('column', 'id'), $request->input('sort', 'desc'))
            ->paginate($request->input('perPage', 10));

        return FAQResource::collection($faqs);
    }

    /**
     * Display Terms & Condition.
     *
     * @return JsonResponse
     */
    public function term(): JsonResponse
    {
        $model = TermCondition::with([
            'translation' => fn($q) => $q->where('locale', $this->language)
        ])->first();

        if (empty($model)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.model_found'), $model);
    }

    /**
     * Display Terms & Condition.
     *
     * @return JsonResponse
     */
    public function policy(): JsonResponse
    {
        $model = PrivacyPolicy::with([
            'translation' => fn($q) => $q->where('locale', $this->language)
        ])->first();

        if (empty($model)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.model_found'), $model);
    }

}
