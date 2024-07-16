<?php

namespace App\Exports;

use App\Models\Shop;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionExport extends BaseExport implements FromCollection, WithHeadings
{
    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $models = Transaction::orderBy('id')->get();

        return $models->map(fn (Transaction $transaction) => $this->tableBody($transaction));
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'id',
            'Payable type',
            'Payable id',
            'Price',
            'User Id',
            'Payment SYS Id',
            'Payment TRX Id',
            'Note',
            'Perform Time',
            'Refund Time',
            'Status',
            'Status Description',
            'Created At',
            'Updated At',
            'Deleted At',
        ];
    }

    /**
     * @param Transaction $model
     * @return array
     */
    private function tableBody(Transaction $model): array
    {
        return [
            'id'                    => $model->id,
            'payable_type'          => $model->payable_type,
            'payable_id'            => $model->payable_id,
            'price'                 => $model->price,
            'user_id'               => $model->user_id,
            'payment_sys_id'        => $model->payment_sys_id,
            'payment_trx_id'        => $model->payment_trx_id,
            'note'                  => $model->note,
            'perform_time'          => $model->perform_time,
            'refund_time'           => $model->refund_time,
            'status'                => $model->status,
            'status_description'    => $model->status_description,
            'created_at'            => $model->created_at,
            'updated_at'            => $model->updated_at,
            'deleted_at'            => $model->deleted_at,
        ];
    }
}
