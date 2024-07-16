<?php

namespace App\Repositories\BannerRepository;

use App\Models\Banner;
use App\Models\Language;
use App\Repositories\CoreRepository;

class BannerRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Banner::class;
    }

    public function bannersPaginate(array $filter)
    {
        return $this->model()
            ->whereHas('products', fn($q) => $q
                ->whereHas('stock', function ($item) {
                    $item->where('quantity', '>', 0);
                })
            )
            ->withCount('likes')
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', $type);
            })
            ->when(data_get($filter, 'product_ids'), function ($q, $productIds) {
                $q->whereHas('products', fn($q) => $q->whereIn('id', $productIds));
            })
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) {
                $q->whereHas('products', fn($q) => $q->where('shop_id', $shopId));
            })
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
            ->select([
                'id',
                'url',
                'type',
                'img',
                'active',
                'created_at',
                'updated_at',
                'deleted_at',
                'clickable',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function bannerDetails(int $id, array $filter = [])
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->withCount('likes')
            ->withCount('products')
            ->with([
                'galleries',
                'products:id,uuid,user_id,status,logo_img,open,delivery_time',
                'products' => fn($q) => $q
                    ->filter($filter)
                    ->withCount('stocks')
                    ->with([
                        'stock' => fn($q) => $q->where('quantity', '>', 0),
                        'stock.bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
                            'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                            'bonus_quantity', 'value', 'type', 'status'
                        ]),
//                        'stock.bonus.stock',
//                        'stock.bonus.stock.countable:id,uuid,tax,bar_code,status,active,img,min_qty,max_qty',
//                        'stock.bonus.stock.countable.translation' => fn($q) => $q->select('id', 'product_id', 'title', 'locale'),
                        'stock.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                        'discounts' => fn($q) => $q
                            ->where('start', '<=', today())
                            ->where('end', '>=', today())
                            ->where('active', 1),
                        'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                    ])
                    ->whereHas('stock', function ($item) {
                        $item->where('quantity', '>', 0);
                    })
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->paginate(data_get($filter, 'perPage', 10)),
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'translations',
            ])
            ->whereHas('products', fn($q) => $q
                ->whereHas('stock', function ($item) {
                    $item->where('quantity', '>', 0);
                })
            )
            ->find($id);
    }
}
