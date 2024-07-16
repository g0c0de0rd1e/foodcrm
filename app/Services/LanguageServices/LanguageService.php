<?php

namespace App\Services\LanguageServices;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Models\Language;
use App\Services\CoreService;
use App\Services\Interfaces\LanguageServiceInterface;
use Exception;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class LanguageService extends CoreService implements LanguageServiceInterface
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Language::class;
    }

    public function create(array $data): array
    {
        try {
            /** @var Language $language */
            $language = $this->model();

            $language = $language->withTrashed()->updateOrCreate([
                'locale' => data_get($data, 'locale'),
            ], $data + ['deleted_at' => null]);

            $this->setDefault($language->id, data_get($data, 'default'));

            if (data_get($data, 'images.0')) {

                $language->update(['img' => data_get($data, 'images.0')]);
                $language->uploads(data_get($data, 'images'));

            }

            try {
                Cache::delete('languages-list');
            } catch (InvalidArgumentException $e) {
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $language];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Language $language
     * @param array $data
     * @return array
     */
    public function update(Language $language, array $data): array
    {
        try {
            $language->update($data);

            $default =  $language->default ?: data_get($data, 'default');

            $this->setDefault($language->id, $default);

            if (data_get($data, 'images.0')) {

                $language->galleries()->delete();
                $language->update(['img' => data_get($data, 'images.0')]);
                $language->uploads(data_get($data, 'images'));

            }

            try {
                Cache::delete('languages-list');
            } catch (InvalidArgumentException $e) {
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $language];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_400];
        }
    }

    /**
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {
        foreach (Language::whereIn('id', is_array($ids) ? $ids : [])->get() as $language) {

            /** @var Language $language */

            if ($language->default) {
                continue;
            }

            FileHelper::deleteFile("images/languages/$language->img");

            $language->delete();
        }

        try {
            Cache::delete('languages-list');
        } catch (InvalidArgumentException $e) {
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function setLanguageDefault(int $id = null, int $default = null): array
    {
        $item = $this->model()->find($id);

        if (!$item) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            Cache::delete('languages-list');
        } catch (InvalidArgumentException $e) {
        }

        return $this->setDefault($id, $default);
    }

}
