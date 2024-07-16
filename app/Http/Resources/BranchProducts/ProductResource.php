<?php

namespace App\Http\Resources\BranchProducts;

use App\Http\Resources\SimpleDiscountResource;
use App\Http\Resources\TranslationResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Product|JsonResource $this */

        return [
            'id'                    => $this->when($this->id, $this->id),
            'uuid'                  => $this->when($this->uuid, $this->uuid),
            'shop_id'               => $this->when($this->shop_id, $this->shop_id),
            'parent_id'             => $this->when($this->parent_id, $this->parent_id),
            'category_id'           => $this->when($this->category_id, $this->category_id),
            'keywords'              => $this->when($this->keywords, $this->keywords),
            'brand_id'              => $this->when($this->brand_id, $this->brand_id),
            'tax'                   => $this->when($this->tax, $this->tax),
            'bar_code'              => $this->when($this->bar_code, $this->bar_code),
            'qr_code'               => $this->when($this->qr_code, $this->qr_code),
            'status'                => $this->when($this->status, $this->status),
            'active'                => (bool) $this->active,
            'addon'                 => (bool) $this->addon,
            'visibility'            => (bool) $this->visibility,
            'vegetarian'            => (bool) $this->vegetarian,
            'img'                   => $this->when($this->img, $this->img),
            'stocks_count'          => $this->when($this->stocks_count, $this->stocks_count, 0),
            'kcal'                  => $this->when($this->kcal, $this->kcal),
            'carbs'                 => $this->when($this->carbs, $this->carbs),
            'protein'               => $this->when($this->protein, $this->protein),
            'fats'                  => $this->when($this->fats, $this->fats),
            'min_qty'               => $this->when($this->min_qty, $this->min_qty),
            'max_qty'               => $this->when($this->max_qty, $this->max_qty),
            'created_at'            => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'            => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),
            'reviews_count'         => $this->whenLoaded('reviews', $this->reviews_count),
            'rating_percent'        => $this->whenLoaded('reviews', $this->reviews->avg('rating')),

            // Relations
            'discounts'             => SimpleDiscountResource::collection($this->whenLoaded('discounts')),
            'translation'           => TranslationResource::make($this->whenLoaded('translation')),
            'stock'                 => SimpleStockResource::make($this->whenLoaded('stock')),
        ];
    }

}
