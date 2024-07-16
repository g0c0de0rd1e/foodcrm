<?php

namespace App\Services\TermService;

use App\Helpers\ResponseError;
use App\Models\TermCondition;
use App\Services\CoreService;
use App\Traits\SetTranslations;

class TermService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return TermCondition::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $term = TermCondition::firstOrCreate();

        $this->setTranslations($term, $data);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $term];
    }

}
