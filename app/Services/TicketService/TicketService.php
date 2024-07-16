<?php

namespace App\Services\TicketService;

use App\Helpers\ResponseError;
use App\Models\Ticket;
use App\Services\CoreService;
use Exception;

class TicketService extends CoreService
{
    protected function getModelClass(): string
    {
        return Ticket::class;
    }

    public function create(array $data): array
    {
        try {
            $ticket = $this->model()->create($data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $ticket];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(Ticket $ticket, array $data): array
    {
        try {
            $result = $ticket->update($data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $result];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

    public function setStatus(int $id, ?string $status) {

        $ticket = Ticket::find($id);

        if (empty($ticket)) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $status = $status ?: $ticket->status;

        if (!in_array($status, Ticket::STATUS)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_253, 'data' => ['ASD']]);
        }

        $ticket->update(['status' => $status]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $ticket];
    }
}
