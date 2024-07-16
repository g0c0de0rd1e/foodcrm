<?php

namespace App\Services;

use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Language;
use App\Traits\ApiResponse;
use App\Traits\Loggable;
use Cache;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

abstract class CoreService
{
    use ApiResponse, Loggable;

    private   $model;
    protected $language;
    protected $currency;

    public function __construct()
    {
        $this->model    = app($this->getModelClass());
        $this->language = $this->setLanguage();
        $this->currency = $this->setCurrency();
    }

    abstract protected function getModelClass();

    protected function model()
    {
        return clone $this->model;
    }

    /**
     * Set Default status of Model
     * @param int|null $id
     * @param int|null $default
     * @param null $user
     * @return array
     */
    public function setDefault(int $id = null, int $default = null, $user = null): array
    {
        $model = $this->model()->orderByDesc('id')
            ->when(isset($user), function ($q) use($user) {
            $q->where('user_id', $user);
        })->get();

        // Check Languages list, if first records set it default.
        if (count($model) <= 1) {
            $this->model()->first()->update(['default' => 1, 'active' => 1]);
        }

        // Check and update default language if another language came with DEFAULT
        if ($default) {

            $defaultItem = $this->model()->orderByDesc('id')
                ->when(isset($user), function ($q) use($user) {
                    $q->where('user_id', $user);
                })->whereDefault(1)->first();

            if (!empty($defaultItem)) {
                $defaultItem->update(['default' => 0]);
            }

            if ($id) {
                $item = $this->model()->orderByDesc('id')
                    ->when(isset($user), function ($q) use ($user) {
                        $q->where('user_id', $user);
                    })->find($id);
                $item->update(['default' => 1, 'active' => 1]);
            }
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    /**
     * Set default Currency
     */
    protected function setCurrency()
    {
        return request(
            'currency_id',
            data_get(Currency::where('default', 1)->first(['id', 'default']), 'id')
        );
    }

    /**
     * Set default Language
     */
    protected function setLanguage()
    {
        return request(
            'lang',
            data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale')
        );
    }

    public function dropAll(?array $exclude = []): array
    {
        /** @var Model|Language $models */

        $models = $this->model();

        $models = $models->when(data_get($exclude, 'column') && data_get($exclude, 'value'),
            function (Builder $query) use($exclude) {

                $query->where(data_get($exclude, 'column'), '!=', data_get($exclude, 'value'));
            }
        )->get();

        foreach ($models as $model) {

            try {

                $model->delete();

            } catch (Throwable $e) {

                $this->error($e);

            }

        }

        Cache::flush();

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function restoreAll()
    {
        /** @var Model|Language $models */
        $models = $this->model();

        foreach ($models->withTrashed()->whereNotNull('deleted_at')->get() as $model) {

            try {

                $model->update([
                    'deleted_at' => null
                ]);

            } catch (Throwable $e) {

                $this->error($e);

            }

        }

        Cache::flush();

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function truncate(string $name = '')
    {
        DB::statement("SET foreign_key_checks = 0");
        DB::table($name ?: $this->model()->getTable())->truncate();
        DB::statement("SET foreign_key_checks = 1");

        Cache::flush();

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function destroy(array $ids)
    {
        foreach ($this->model()->whereIn('id', $ids)->get() as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

        Cache::flush();
    }

    public function delete(array $ids)
    {
        $this->destroy($ids);
        Cache::flush();
    }

    public function remove(array $ids, string $column = 'id'): array
    {
        $errorIds = [];

        foreach ($this->model()->whereIn($column, $ids)->get() as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
                $errorIds[] = $model->id;
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_505,
            'message' => __(
                'errors.' . ResponseError::CANT_DELETE_IDS,
                [
                    'ids' => implode(', ', $errorIds)
                ],
                $this->language
            )
        ];
    }

}
