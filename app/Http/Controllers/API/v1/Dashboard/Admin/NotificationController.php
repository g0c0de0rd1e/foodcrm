<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Notification\StoreRequest;
use App\Http\Requests\Notification\UpdateRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Repositories\NotificationRepository\NotificationRepository;
use App\Services\NotificationService\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends AdminBaseController
{
    private NotificationRepository $repository;
    private NotificationService $service;

    /**
     * @param NotificationRepository $repository
     * @param NotificationService $service
     */
    public function __construct(NotificationRepository $repository, NotificationService $service)
    {
        parent::__construct();
        $this->repository   = $repository;
        $this->service      = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $shopsWithClosedDays = $this->repository->paginate($request->all());

        return NotificationResource::collection($shopsWithClosedDays);
    }

    /**
     * NOT USED
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(ResponseError::NO_ERROR, []);
    }

    /**
     * Display the specified resource.
     *
     * @param Notification $notification
     * @return JsonResponse
     */
    public function show(Notification $notification): JsonResponse
    {
        $notification = $this->repository->show($notification);

        return $this->successResponse(ResponseError::NO_ERROR, NotificationResource::make($notification));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Notification $notification
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Notification $notification, UpdateRequest $request): JsonResponse
    {
        $result = $this->service->update($notification, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_was_successfully_create'), []);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(__('web.record_has_been_successfully_delete'), []);
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
