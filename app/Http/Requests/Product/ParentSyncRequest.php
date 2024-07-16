<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ParentSyncRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'products'      => 'required|array',
            'products.*'    => [
                'required',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at')
                    ->where('visibility', true)
            ],
        ];
    }
}
