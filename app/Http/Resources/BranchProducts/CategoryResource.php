<?php

namespace App\Http\Resources\BranchProducts;

use App\Http\Resources\TranslationResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Category|JsonResource $this */
        return [
            'id'                => $this->id,
            'uuid'              => $this->when($this->uuid, $this->uuid),
            'keywords'          => $this->when($this->keywords, $this->keywords),
            'parent_id'         => $this->when($this->parent_id, $this->parent_id),
            'type'              => $this->when($this->type, $this->type),
            'img'               => $this->img,
            'active'            => (bool) $this->active,
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'        => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),
            'products_count'    => $this->when($this->products_count, $this->products_count),
            'stocks_count'      => $this->when($this->stocks_count, $this->stocks_count),
            'receipts_count'    => $this->when($this->receipts_count, $this->receipts_count),

            'translation'       => TranslationResource::make($this->whenLoaded('translation')),
            'products'          => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
