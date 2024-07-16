<?php

namespace App\Services\DeliveryManSettingService;

use App\Helpers\ResponseError;
use App\Models\DeliveryManSetting;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Throwable;

class DeliveryManSettingService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return DeliveryManSetting::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            /** @var DeliveryManSetting $deliveryManSetting */
            $deliveryManSetting = $this->model()->updateOrCreate([
                'user_id' => data_get($data, 'user_id')
            ], $data);

            $this->setTranslations($deliveryManSetting, $data);

            if (data_get($data, 'images.0')) {
                $deliveryManSetting->uploads(data_get($data, 'images'));
                $deliveryManSetting->update(['img' => data_get($data, 'images.0')]);
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $deliveryManSetting];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function update(DeliveryManSetting $deliveryManSetting, array $data): array
    {
        try {

            $deliveryManSetting->update($data);

            $this->setTranslations($deliveryManSetting, $data);

            if (data_get($data, 'images.0')) {
                $deliveryManSetting->galleries()->delete();
                $deliveryManSetting->uploads(data_get($data, 'images'));
                $deliveryManSetting->update(['img' => data_get($data, 'images.0')]);
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $deliveryManSetting];

        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => ResponseError::ERROR_400];
        }
    }

    public function createOrUpdate(array $data): array
    {
        try {
            $data['user_id'] = auth('sanctum')->id();

            /** @var DeliveryManSetting $deliveryManSetting */
            $deliveryManSetting = $this->model()->updateOrCreate([
                'user_id' => $data['user_id']
            ], $data);

            $this->setTranslations($deliveryManSetting, $data);

            if (data_get($data, 'images.0')) {
                $deliveryManSetting->galleries()->delete();
                $deliveryManSetting->uploads(data_get($data, 'images'));
                $deliveryManSetting->update(['img' => data_get($data, 'images.0')]);
            }

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $deliveryManSetting->loadMissing(['galleries', 'deliveryMan'])
            ];

        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function updateLocation(array $data): array
    {
        try {
            $deliveryManSetting = DeliveryManSetting::where('user_id', auth('sanctum')->id())->first();

            if (empty($deliveryManSetting)) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::DELIVERYMAN_SETTING_EMPTY, locale: $this->language)
                ];
            }

            $deliveryManSetting->update($data + ['updated_at' => now()]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $deliveryManSetting];

        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }

    public function updateOnline(): array
    {
        try {
            $deliveryManSetting = DeliveryManSetting::where('user_id', auth('sanctum')->id())->first();

            $deliveryManSetting->update([
               'online' => !$deliveryManSetting->online
            ]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $deliveryManSetting];

        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function destroy(?array $ids = []) {

        $deliveryManSettings = DeliveryManSetting::whereIn('id', is_array($ids) ? $ids : [])->get();

        foreach ($deliveryManSettings as $deliveryManSetting) {
            $deliveryManSetting->delete();
        }

    }
}
