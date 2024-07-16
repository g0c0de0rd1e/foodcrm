<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\GalleryUploadRequest;
use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use App\Services\GalleryService\FileStorageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GalleryController extends Controller
{
    use ApiResponse;

    private Gallery $model;
    private FileStorageService $storageService;

    public function __construct(Gallery $model, FileStorageService $storageService)
    {
        parent::__construct();

        $this->middleware(['sanctum.check'])->except('store');

        $this->model            = $model;
        $this->storageService   = $storageService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function getStorageFiles(FilterParamsRequest $request): JsonResponse
    {
        $type = request('type');

        if (!in_array($type, Gallery::TYPES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_432]);
        }

        $files = $this->storageService->getStorageFiles($type, (int)$request->input('perPage', 10));

        return $this->successResponse(__('web.list_of_storage_files'), $files);
    }

    /**
     * Destroy a file from the storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteStorageFile(Request $request): JsonResponse
    {
        if (!is_array($request->input('ids'))) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->storageService->deleteFileFromStorage($request->input('ids'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            trans('web.successfully_deleted',  [], $this->language),
            data_get($result, 'data')
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $galleries = $this->model->orderByDesc('id')->paginate($request->input('perPage', 15));

        return GalleryResource::collection($galleries);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param GalleryUploadRequest $request
     * @return JsonResponse
     */
    public function store(GalleryUploadRequest $request): JsonResponse
    {
        if (!$request->file('image')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = FileHelper::uploadFile($request->file('image'), $request->input('type', 'unknown'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            trans('web.image_successfully_uploaded', [], $this->language),
            [
                'title' => data_get($result, 'data'),
                'type'  => $request->input('type')
            ]
        );

    }
}
