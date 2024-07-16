<?php

namespace App\Services\NotificationService;

use App\Helpers\ResponseError;
use App\Models\Notification;
use App\Services\CoreService;
use Throwable;

class NotificationService extends CoreService
{
    protected function getModelClass(): string
    {
        return Notification::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            $this->model()->updateOrCreate($data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => ResponseError::ERROR_501, 'code' => ResponseError::ERROR_501];
        }
    }

    public function update(Notification $notification, array $data): array
    {
        try {

            $notification->update($data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function delete(?array $ids = []) {

        foreach (Notification::find(is_array($ids) ? $ids : []) as $notification) {
            $notification->delete();
        }

    }
}
