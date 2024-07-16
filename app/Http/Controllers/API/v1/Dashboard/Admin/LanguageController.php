<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Language\StoreRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\Interfaces\LanguageServiceInterface;
use Illuminate\Http\JsonResponse;

class LanguageController extends AdminBaseController
{
    private LanguageServiceInterface $service;
    private Language $model;

    public function __construct(LanguageServiceInterface $service, Language $model)
    {
        parent::__construct();
        $this->service  = $service;
        $this->model    = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $languages = $this->model->languagesList();

        return $this->successResponse(__('web.list_of_languages'), LanguageResource::collection($languages));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_was_successfully_create'),
            LanguageResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Language $language
     * @return JsonResponse
     */
    public function show(Language $language): JsonResponse
    {
        return $this->successResponse(
            __('web.language_found'),
            LanguageResource::make($language)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Language $language
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(Language $language, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update($language, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            LanguageResource::make(data_get($result, 'data'))
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
        $this->service->delete($request->input('ids', []));

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

    /**
     * Get Language where "default = 1".
     *
     * @return JsonResponse
     */
    public function getDefaultLanguage(): JsonResponse
    {
        $language = $this->model->whereDefault(1)->first();

        if (empty($language)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('web.language_found'),
            LanguageResource::make($language)
        );
    }

    /**
     * Make specific Language as default
     * @param int $id
     * @return JsonResponse
     */
    public function setDefaultLanguage(int $id): JsonResponse
    {
        $result = $this->service->setLanguageDefault($id, 1);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.item_is_default_now'));
    }

    /**
     * Get all Active languages
     * @return JsonResponse
     */
    public function getActiveLanguages(): JsonResponse
    {
        $languages = $this->model->whereActive(1)->get();

        return $this->successResponse(
            __('web.list_of_active_languages'),
            LanguageResource::collection($languages)
        );
    }

    /**
     * Remove Model image from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function imageDelete(int $id): JsonResponse
    {
        $language = $this->model->find($id);

        if (empty($language)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $language->galleries()->delete();

        $language->update(['img' => null]);

        return $this->successResponse(__('web.image_has_been_successfully_delete'), $language);
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setActive(int $id): JsonResponse
    {
        $lang = $this->model->find($id);

        if (empty($lang)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $lang->update(['active' => !$lang->active]);

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            LanguageResource::make($lang)
        );
    }

}
