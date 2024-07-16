<?php

namespace App\Services\BonusService;

use App\Helpers\ResponseError;
use App\Models\Bonus;
use App\Services\CoreService;

class BonusService extends CoreService
{

    protected function getModelClass(): string
    {
        return Bonus::class;
    }

    public function create(array $data): array
    {

        $bonus = $this->model()->create($data);

        /** @var Bonus $bonus */

        if (!$bonus) {
            return ['status' => false, 'code' => ResponseError::ERROR_501];
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function update(Bonus $bonus, array $collection): array
    {
        $bonus->update($collection);

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        foreach (Bonus::whereIn('id', is_array($ids) ? $ids : [])->where('shop_id', $shopId)->get() as $bonus) {
            $bonus->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function statusChange(int $id): array
    {
        /** @var Bonus $bonus */
        $bonus = $this->model()->find($id);

        if (!$bonus) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $bonus->update(['status' => !$bonus->status]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $bonus->loadMissing(['bonusable', 'stock'])];
    }
}
