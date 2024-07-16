<?php

namespace App\Services\BlogService;

use App\Helpers\ResponseError;
use App\Models\Blog;
use App\Services\CoreService;
use Exception;
use Illuminate\Support\Str;
use Throwable;

final class BlogService extends CoreService
{
    protected function getModelClass(): string
    {
        return Blog::class;
    }

    public function create(array $data): array
    {
        try {
            $data['type'] = data_get(Blog::TYPES, data_get($data, 'type', 'blog'));

            $blog = $this->model()->create([
                'uuid'      => Str::uuid(),
                'user_id'   => auth('sanctum')->id(),
            ] + $data);

            $this->setTranslations($blog, $data);

            if (data_get($data, 'images.0')) {

                $blog->uploads(data_get($data, 'images'));
                $blog->update([
                    'img' => data_get($data, 'images.0')
                ]);

            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $blog];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => ResponseError::ERROR_501];
        }
    }

    public function update(string $uuid, $data): array
    {
        try {
            $blog = $this->model()->where('uuid', $uuid)->first();

            if (empty($blog)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            $data['type'] = data_get(Blog::TYPES, data_get($data, 'type', 'blog'));
            $blog->update($data);

            $this->setTranslations($blog, $data);

            if (data_get($data, 'images.0')) {
                $blog->galleries()->delete();
                $blog->uploads(data_get($data, 'images'));
                $blog->update([
                    'img' => data_get($data, 'images.0')
                ]);

            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => ResponseError::ERROR_400];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (Blog::whereIn('id', is_array($ids) ? $ids : [])->get() as $blog) {

            /** @var Blog $blog */

            try {
                $blog->galleries()->delete();
            } catch (Throwable $e) {

            }

            $blog->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function setActiveStatus(Blog $blog): array
    {
        try {
            $blog->update(['active' => !$blog->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

    public function blogPublish(Blog $blog): array
    {
        try {
            $blog->update(['published_at' => today()]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

    private function setTranslations(Blog $blog, array $data)
    {
        $titles = data_get($data, 'title');

        if (is_array($titles)) {
            $blog->translations()->forceDelete();
        }

        foreach (is_array($titles) ? $titles : [] as $index => $value) {

            $blog->translation()->create([
                'locale'        => $index,
                'title'         => $value,
                'short_desc'    => data_get($data, "short_desc.$index"),
                'description'   => data_get($data, "description.$index"),
            ]);

        }
    }
}
