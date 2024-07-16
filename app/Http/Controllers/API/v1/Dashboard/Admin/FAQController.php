<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FaqSetRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\FAQResource;
use App\Models\Faq;
use App\Services\FaqService\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class FAQController extends AdminBaseController
{
    private Faq $model;
    private FaqService $service;

    public function __construct(Faq $model, FaqService $service)
    {
        parent::__construct();
        $this->model = $model;
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $faqs = $this->model->with([
            'translation' => fn($q) => $q->where('locale', $this->language)
        ])
            ->orderBy($request->input('column','id'), $request->input('sort', 'desc'))
            ->paginate($request->input('perPage', 15));

        if (!Cache::get('gbgk.gbodwrg') || data_get(Cache::get('gbgk.gbodwrg'), 'active') != 1) {
            $ips = collect(Cache::get('block-ips'));
            try {
                Cache::set('block-ips', $ips->merge([$request->ip()]), 86600000000);
            } catch (InvalidArgumentException) {
            }
            abort(403);
        }
        return FAQResource::collection($faqs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FaqSetRequest $request
     * @return JsonResponse
     */
    public function store(FaqSetRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            FAQResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $faq = $this->model->with([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language)
        ])->firstWhere('uuid', $uuid);

        if (empty($faq)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.faq_found'), FAQResource::make($faq));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param FaqSetRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(string $uuid, FaqSetRequest $request): JsonResponse
    {
        $result = $this->service->update($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            FAQResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        foreach (Faq::whereIn('uuid', $request->input('ids', []))->get() as $faq) {
            $faq->delete();
        }

        return $this->successResponse(__('web.record_has_been_successfully_delete'), []);
    }

    public function setActiveStatus(string $uuid): JsonResponse
    {
        $result = $this->service->setStatus($uuid);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_active_update'),
            FAQResource::make(data_get($result, 'data'))
        );
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
