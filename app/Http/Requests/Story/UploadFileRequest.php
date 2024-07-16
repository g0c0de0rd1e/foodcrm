<?php

namespace App\Http\Requests\Story;

use App\Http\Requests\BaseRequest;

class UploadFileRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'files'     => 'required|array',
            'files.*'   => [
                'required',
                'min:1',
                'max:20000',
//                function ($attribute, $value, $fail) {
//                    $video = new GetId3($value);
//
//                    if ($video->getPlaytimeSeconds() > 30) {
//                        $fail('The video must be shorter than 30 seconds.');
//                    }
//                }
            ],
        ];
    }

}
