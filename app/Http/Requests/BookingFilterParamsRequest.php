<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BookingFilterParamsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'sort'          => 'string|in:asc,desc',
            'column'        => 'regex:/^[a-zA-Z-_]+$/',
            'status'        => 'string',
            'perPage'       => 'integer|min:1|max:100',
            'shop_id'       => [
                'integer',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ],
            'user_id'       => 'exists:users,id',
            'date_from'     => 'date_format:Y-m-d H:i',
            'date_to'       => 'date_format:Y-m-d H:i',
            'ids'           => 'array',
        ];
    }

}
