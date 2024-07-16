<?php

namespace App\Repositories\ShopRepository;

use App\Helpers\Utility;
use App\Http\Resources\BranchProducts\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DeliveryZone;
use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\ShopTag;
use App\Models\Stock;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\ShopRepoInterface;
use Cache;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\SimpleCache\InvalidArgumentException;

class ShopRepository extends CoreRepository implements ShopRepoInterface
{
    protected function getModelClass(): string
    {
        return Shop::class;
    }

    /**
     * Get all Shops from table```
     */
    public function shopsList(array $filter = []): array|Collection|\Illuminate\Support\Collection
    {
        /** @var Shop $shop */

        $shop = $this->model();

        return $shop
            ->updatedDate($this->updatedDate)
            ->filter($filter)
            ->with([
                'translation'   => fn($q) => $q->where('locale', $this->language),
                'seller'        => fn($q) => $q->select('id', 'firstname', 'lastname', 'uuid'),
                'seller.roles',
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->orderByDesc('id')
            ->limit(data_get($filter, 'perPage', 1))
            ->offset(data_get($filter, 'page', 0))
            ->get();
    }

    /**
     * Get one Shop by UUID
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->with([
                'translation' => function ($query) use ($filter) {

                    $query->when(data_get($filter, 'not_lang'),
                        fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                        fn($q) => $q->where('locale', '=', $this->language),
                    );

                },
                'bonus' => fn($q) => $q->where('expired_at', '>=', now())
                    ->select([
                        'bonusable_type',
                        'bonusable_id',
                        'bonus_quantity',
                        'bonus_stock_id',
                        'expired_at',
                        'value',
                        'type',
                    ]),
                'bonus.stock.countable:id,uuid',
                'bonus.stock.countable.translation' => fn($q) => $q->where('locale', $this->language)
                    ->select('id', 'locale', 'title', 'product_id'),
                'closedDates',
                'workingDays' => fn($q) => $q->when(data_get($filter, 'work_24_7'),
                    fn($b) => $b->where('from', '01-00')->where('to', '>=', '23-00')
                ),
                'discounts' => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                    ->select('id', 'shop_id', 'end', 'active'),
            ])
            ->whereHas('translation', function ($query) use ($filter) {

                $query->when(data_get($filter, 'not_lang'),
                    fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                    fn($q) => $q->where('locale', '=', $this->language),
                );

            })
            ->when(data_get($filter, 'prices'), function (Builder $q, $prices) {

                $to   = data_get($prices, 0, 0) / $this->currency;
                $from = data_get($prices, 1, 0) / $this->currency;

                $q->whereHas('products.stocks', fn($q) => $q->where([
                    ['price', '>=', $to],
                    ['price', '<=', $to >= $from ? Stock::max('price') : $from],
                ]));

            })
            ->select([
                'id',
                'uuid',
                'logo_img',
                'background_img',
                'status',
                'type',
                'delivery_time',
                'open',
                'visibility',
                'location',
            ])
            ->withCount('reviews')
            ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                $rtg = [
                    0 => data_get($rating, 0, 0),
                    1 => data_get($rating, 1, 5),
                ];

                $q->withAvg([
                    'reviews' => fn(Builder $b) => $b->whereBetween('rating', $rtg)
                ], 'rating')->whereHas('reviews',
                    fn(Builder $b) => $b->whereBetween('rating', $rtg)
                );

            },
                fn($q) => $q->withAvg('reviews', 'rating')
            )
            ->when(data_get($filter, 'order_by'), function (Builder $query, $orderBy) {

                switch ($orderBy) {
                    case 'new':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'old':
                        $query->orderBy('created_at');
                        break;
                    case 'best_sale':
                        $query->withCount('orders')->orderBy('orders_count', 'desc');
                        break;
                    case 'low_sale':
                        $query->withCount('orders')->orderBy('orders_count');
                        break;
                    case 'high_rating':
                        $query->orderBy('reviews_avg_rating', 'desc');
                        break;
                    case 'low_rating':
                        $query->orderBy('reviews_avg_rating');
                        break;
                    case 'trust_you':
                        $ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
                        if (!empty($ids)) {
                            $query->orderByRaw(DB::raw("FIELD(id, $ids) ASC"));
                        }
                        break;
                }

            })
            ->when(data_get($filter, 'column'), function (Builder $query, $column) use ($filter) {
                $query->orderBy(
                    $column,
                    data_get($filter, 'sort', 'desc')
                );
            })
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get one Shop by UUID
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function selectPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->select('id', 'locale', 'title', 'shop_id'),
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->select([
                'id',
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param string $uuid
     * @return Model|Builder|null
     */
    public function shopDetails(string $uuid): Model|Builder|null
    {
        /** @var Shop $shop */
        $shop   = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $shop->with([
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'workingDays',
            'closedDates',
            'bonus' => fn($q) => $q->where('expired_at', '>=', now())
                ->select([
                    'bonusable_type',
                    'bonusable_id',
                    'bonus_quantity',
                    'bonus_stock_id',
                    'expired_at',
                    'value',
                    'type',
                ]),
            'bonus.stock.countable' => fn($q) => $q->select('id', 'uuid'),
            'bonus.stock.countable.translation' => fn($q) => $q->where('locale', $this->language)
                ->select('id', 'locale', 'title', 'product_id'),
            'discounts' => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                ->select('id', 'shop_id', 'end', 'active'),
            'shopPayments:id,payment_id,shop_id,status,client_id,secret_id',
            'shopPayments.payment:id,tag,input,sandbox,active',
        ])
//            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where(fn($q) => $q->where('uuid', $uuid)->orWhere('id', $uuid))
            ->first();
    }

    /**
     * @return Collection|array
     */
    public function takes(): Collection|array
    {
        return ShopTag::with([
            'translation' => fn($q) => $q->where('locale', $this->language),
        ])->get();
    }

    /**
     * @return mixed
     */
    public function productsAvgPrices(): mixed
    {
        return Cache::remember('products-avg-prices', 86400, function () {

            $min = Stock::where('price', '>', 0)->whereHas('countable')->min('price');
            $max = Stock::where('price', '>', 0)->whereHas('countable')->max('price');

            return [
                'min' => $min * $this->currency,
                'max' => ($min === $max ? $max + 1 : $max) * $this->currency,
            ];
        });
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopsSearch(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop
            ->filter($filter)
            ->with([
                'translation'   => fn($q) => $q->where('locale', $this->language),
                'discounts'     => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                    ->select('id', 'shop_id', 'end', 'active'),
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->latest()
            ->select([
                'id',
                'logo_img',
                'status',
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function shopsByIDs(array $filter): mixed
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop->with([
            'translation'   => fn($q) => $q->where('locale', $this->language),
            'discounts'     => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                ->select('id', 'shop_id', 'end', 'active'),
            'tags:id,img',
            'tags.translation' => fn($q) => $q->where('locale', $this->language),
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
            ->find(data_get($filter, 'shops', []));
    }

    /**
     * @return object|null
     */
    public function mainShop(): object|null
    {
        return Shop::with([
            'translation' => fn($q) => $q->where('locale', $this->language),
            'tags:id,img',
            'tags.translation' => fn($q) => $q->where('locale', $this->language),
            'workingDays',
            'closedDates',
        ])
            ->whereNull('parent_id')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->first();
    }

    public function recommended(array $filter): array
    {
//        if (!empty(Cache::get('shops-recommended'))) {
//            return Cache::get('shops-recommended');
//        }

        $recommendedCount = (int)(Settings::adminSettings()->where('key', 'recommended_count')->first()?->value ?? 2);

        $shops = Shop::filter($filter)
            ->with([
                'orders' => fn($q) => $q->whereHas('orderDetails')
                    ->with([
                        'orderDetails' => fn($od) => $od->with([
                            'stock' => fn($s) => $s->with([
                                'countable' => fn($c) => $c->where('active', true)->where('addon', false)
                                    ->where('status', Product::PUBLISHED)
                            ])
                                ->whereHas('countable', fn($c) => $c->where('active', true)
                                    ->where('addon', false)
                                    ->where('status', Product::PUBLISHED)
                                )
                                ->where('quantity', '>', 0)
                        ])->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)),
                    ])
                    ->whereStatus(Order::STATUS_DELIVERED)
                    ->where('created_at', '>=', date('Y-m-d 00:00:01', strtotime('-30 days'))),
                'translation' => fn($q) => $q->where('locale', $this->language),
            ])
            ->whereHas('orders', fn($q) => $q
                ->whereStatus(Order::STATUS_DELIVERED)
                ->where('created_at', '>=', date('Y-m-d 00:00:01', strtotime('-30 days'))),
            )
            ->select([
                'id',
                'uuid',
                'background_img',
                'logo_img',
            ])
            ->where('open', 1)
            ->where('status', 'approved')
            ->get();

        $result = [];
        $cache  = [];

        foreach ($shops as $shop) {

            /** @var Shop $shop */
            $result[$shop->id] = [];
            $result[$shop->id]['id']               = $shop->id;
            $result[$shop->id]['uuid']             = $shop->uuid;
            $result[$shop->id]['background_img']   = $shop->background_img;
            $result[$shop->id]['logo_img']         = $shop->logo_img;
            $result[$shop->id]['translation']      = $shop->translation;
            $result[$shop->id]['products_count']   = 0;

            foreach ($shop->orders as $order) {

                /** @var Order $order */
                if ($order->orderDetails?->count() === 0) {
                    continue;
                }

                $productsCount = $order->orderDetails
                    ->where('stock.countable.status', Product::PUBLISHED)
                    ->groupBy('stock.countable.id')
                    ->keys()
                    ->toArray();

                if (!empty(data_get($cache, $shop->id))) {
                    $cache[$shop->id] = array_unique(array_merge($productsCount, $cache[$shop->id]));
                } else {
                    $cache[$shop->id] = $productsCount;
                }

            }

            $result[$shop->id]['products_count'] += count(data_get($cache, "$shop->id", []));

            if ((int)data_get($result, "$shop->id.products_count") < $recommendedCount) {
                unset($result[$shop->id]);
            }

        }

        $result = collect($result)->toArray();

        Cache::put('shop-recommended-ids', $cache, 86400); // 1 day

        return array_values($result);
    }

    public function productsRecPaginate(array $filter): AnonymousResourceCollection
    {
        $shopId     = data_get($filter, 'shop_id');
        $locale     = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
        $lang       = $this->language ?? $locale;
        $recPerPage = data_get($filter, 'recPerPage', 10);
        $page       = data_get($filter, 'perPage', 10);
        $currency   = request('currency_id') ?
            Currency::currenciesList()->where('id', (int)request('currency_id'))->first() :
            Currency::currenciesList()->where('default', 1)->first();

        $key = "branch_recommended_products_first_currency_id=$currency?->rate&lang=$lang&page=$page&recPerPage=$recPerPage&shop_id=$shopId";

        $recommendedCount = (int)(Settings::adminSettings()->where('key', 'recommended_count')->first()?->value ?? 2);

        return Cache::remember($key, 3600, function () use ($filter, $shopId, $recommendedCount, $recPerPage) {
            $products = Product::filter($filter)
                ->with([
                    'stock' => fn($q) => $q
                        ->where('addon', false)
                        ->where('quantity', '>', 0)
                        ->whereHas('orderDetails', fn($q) => $q->whereNull('deleted_at'), '>', $recommendedCount),
                    'translation' => fn($q) => $q->where('locale', $this->language),
                    'discounts' => fn($q) => $q
                        ->where('start', '<=', today())
                        ->where('end', '>=', today())
                        ->where('active', 1),
                ])
                ->withCount([
                    'stocks' => fn($q) => $q->where('quantity', '>', 0)
                ])
                ->where('active', true)
                ->where('addon',  false)
                ->where('status', Product::PUBLISHED)
                ->whereHas('stock', fn($q) => $q
                    ->where('addon', false)
                    ->where('quantity', '>', 0)
                    ->whereHas('orderDetails', fn($q) => $q->whereNull('deleted_at'), '>', $recommendedCount)
                )
                ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
                ->select([
                    'id',
                    'uuid',
                    'category_id',
                    'shop_id',
                    'img',
                    'status',
                    'active',
                    'addon',
                ])
                ->where('shop_id', $shopId)
                ->paginate($recPerPage);

            return ProductResource::collection($products);
        });
    }

    /**
     * @param array $filter
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function products(array $filter): mixed
    {
        $shopId         = data_get($filter, 'shop_id');
        $locale         = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
        $lang           = $this->language ?? $locale;
        $productPerPage = data_get($filter, 'productPerPage', 10);
        $perPage        = data_get($filter, 'page', 1);
        $page           = data_get($filter, 'perPage', 10);
        $orderBy        = data_get($filter, 'order_by');
        $currency       = request('currency_id') ?
            Currency::currenciesList()->where('id', (int)request('currency_id'))->first() :
            Currency::currenciesList()->where('default', 1)->first();

        $key = "branch_products_first_currency_id=$currency?->rate&lang=$lang&page=$page&perPage=$perPage&productPerPage=$productPerPage&shop_id=$shopId&order_by=$orderBy";

        return Cache::remember($key, 3600, function () use ($filter, $locale, $shopId, $productPerPage) {

            $categories = $this->firstRememberProduct($filter, $locale, $shopId, $productPerPage);

            return CategoryResource::collection($categories);

        });
    }

    /**
     * @param array $filter
     * @param string $locale
     * @param int|null $shopId
     * @param int $productPerPage
     * @return LengthAwarePaginator
     */
    private function firstRememberProduct(array $filter, string $locale, ?int $shopId, int $productPerPage): LengthAwarePaginator
    {
        $categories = Category::where([
            ['type', Category::MAIN],
            ['active', true],
        ])
            ->with([
                'translation' => function ($q) use($locale) {
                    $q
                        ->select('id', 'locale', 'title', 'category_id')
                        ->where('locale', $this->language)
                        ->orWhere('locale', $locale);
                },
                'products' => fn($q) => $q
                    ->with([
                        'discounts' => fn($q) => $q
                            ->where('start', '<=', today())
                            ->where('end', '>=', today())
                            ->where('active', 1),
                        'translation' => fn($q) => $q->where('locale', $this->language),
                        'stock' => fn($q) => $q->with([
                            'bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
                                'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                                'bonus_quantity', 'value', 'type', 'status'
                            ])
                        ])
                            ->select([
                                'id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon'
                            ])
                            ->where('quantity', '>', 0)
                            ->where('addon', false),

                    ])
                    ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
                    ->withCount('stocks')
                    ->select([
                        'id',
                        'uuid',
                        'category_id',
                        'shop_id',
                        'img',
                        'status',
                        'shop_id',
                        'active',
                        'max_qty',
                        'min_qty',
                        'addon',
                        'visibility',
                        'vegetarian',
                    ])
                    ->where('active', true)
                    ->where('addon', false)
                    ->where('status', Product::PUBLISHED)
                    ->where('shop_id', $shopId)
                    ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                        $rtg = [
                            0 => data_get($rating, 0, 0),
                            1 => data_get($rating, 1, 5),
                        ];

                        $q->whereHas('reviews', fn(Builder $b) => $b->whereBetween('rating', $rtg));

                    },
                        fn($q) => $q->withAvg('reviews', 'rating')
                    )
                    ->when(data_get($filter, 'order_by'), function (Builder $query, $orderBy) {

                        switch ($orderBy) {
                            case 'new':
                                $query->orderBy('created_at', 'desc');
                                break;
                            case 'old':
                                $query->orderBy('created_at');
                                break;
                            case 'best_sale':
                                $query->withCount('orderDetails')->orderBy('order_details_count', 'desc');
                                break;
                            case 'low_sale':
                                $query->withCount('orderDetails')->orderBy('order_details_count');
                                break;
                            case 'high_rating':
                                $query->orderBy('reviews_avg_rating', 'desc');
                                break;
                            case 'low_rating':
                                $query->orderBy('reviews_avg_rating');
                                break;
                            case 'trust_you':
                                $ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
                                if (!empty($ids)) {
                                    $query->orderByRaw(DB::raw("FIELD(id, $ids) ASC"));
                                }
                                break;
                        }

                    },
                        fn($q) => $q->orderBy(
                            data_get($filter, 'column', 'id'),
                            data_get($filter, 'sort', 'desc')
                        )
                    )
            ])
            ->whereHas('products', fn($q) => $q
                ->select([
                    'id',
                    'uuid',
                    'category_id',
                    'shop_id',
                    'img',
                    'status',
                    'shop_id',
                    'active',
                    'max_qty',
                    'min_qty',
                    'addon',
                    'visibility',
                    'vegetarian',
                ])
                ->where('active', true)
                ->where('addon', false)
                ->where('status', Product::PUBLISHED)
                ->where('shop_id', $shopId)
                ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
                ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                    $rtg = [
                        0 => data_get($rating, 0, 0),
                        1 => data_get($rating, 1, 5),
                    ];

                    $q->whereHas('reviews', fn(Builder $b) => $b->whereBetween('rating', $rtg));

                },
                    fn($q) => $q->withAvg('reviews', 'rating')
                )
            )
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
            ->paginate(data_get($filter, 'perPage', 10));

        foreach ($categories as $key => $category) {

            /** @var Category $category */
            $products = $category->products()
                ->with([
                    'discounts' => fn($q) => $q
                        ->where('start', '<=', today())
                        ->where('end', '>=', today())
                        ->where('active', 1),
                    'translation' => fn($q) => $q->where('locale', $this->language),
                    'stock' => fn($q) => $q->with([
                        'bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
                            'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                            'bonus_quantity', 'value', 'type', 'status'
                        ])
                    ])
                        ->select([
                            'id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon'
                        ])
                        ->where('quantity', '>', 0)
                        ->where('addon', false),

                ])
                ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
                ->withCount('stocks')
                ->select([
                    'id',
                    'uuid',
                    'category_id',
                    'shop_id',
                    'img',
                    'status',
                    'shop_id',
                    'active',
                    'max_qty',
                    'min_qty',
                    'addon',
                    'visibility',
                    'vegetarian',
                ])
                ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                    $rtg = [
                        0 => data_get($rating, 0, 0),
                        1 => data_get($rating, 1, 5),
                    ];

                    $q->withAvg([
                        'reviews' => fn(Builder $b) => $b->whereBetween('rating', $rtg)
                    ], 'rating'
                    )->whereHas('reviews', fn(Builder $b) => $b->whereBetween('rating', $rtg));

                },
                    fn($q) => $q->withAvg('reviews', 'rating')
                )
                ->when(data_get($filter, 'order_by'), function (Builder $query, $orderBy) {

                    switch ($orderBy) {
                        case 'new':
                            $query->orderBy('created_at', 'desc');
                            break;
                        case 'old':
                            $query->orderBy('created_at');
                            break;
                        case 'best_sale':
                            $query->withCount('orderDetails')->orderBy('order_details_count', 'desc');
                            break;
                        case 'low_sale':
                            $query->withCount('orderDetails')->orderBy('order_details_count');
                            break;
                        case 'high_rating':
                            $query->orderBy('reviews_avg_rating', 'desc');
                            break;
                        case 'low_rating':
                            $query->orderBy('reviews_avg_rating');
                            break;
                        case 'trust_you':
                            $ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
                            if (!empty($ids)) {
                                $query->orderByRaw(DB::raw("FIELD(id, $ids) ASC"));
                            }
                            break;
                    }

                },
                    fn($q) => $q->orderBy(
                        data_get($filter, 'column', 'id'),
                        data_get($filter, 'sort', 'desc')
                    )
                )
                ->where('shop_id', $shopId)
                ->take($productPerPage)
                ->get();

            $productsCount = $products->count();

            if ($productsCount <= 0) {
                unset($categories[$key]);
                continue;
            }

            $category->setRelation('products_count', $productsCount);
            $category->setRelation('products', $products);
        }

        return $categories;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function categories(array $filter): LengthAwarePaginator
    {
        $shopId = data_get($filter, 'shop_id');

        return Category::where([
            ['type', Category::MAIN],
            ['active', true],
        ])
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->select('id', 'locale', 'title', 'category_id'),
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->whereHas('products', fn($q) => $q
                ->select('id', 'category_id', 'active', 'shop_id', 'status', 'addon')
                ->where('active', true)
                ->where('addon', false)
                ->where('status', Product::PUBLISHED)
                ->where('shop_id', $shopId)
            )
            ->select([
                'id',
                'uuid',
                'keywords',
                'type',
                'active',
                'img',
            ])
            ->orderBy('id')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        $shopId = data_get($filter, 'shop_id');

        if (data_get($filter, 'address.latitude') && empty($shopId)) {

            $orders = [];

            DeliveryZone::list()->map(function (DeliveryZone $deliveryZone) use ($filter, &$orders) {

                if (!$deliveryZone->shop_id) {
                    return null;
                }

                $shop       = $deliveryZone->shop;

                $location   = data_get($deliveryZone->shop, 'location', []);

                $km         = (new Utility)->getDistance($location, data_get($filter, 'address', []));
                $rate       = data_get($filter, 'currency.rate', 1);

                $orders[$deliveryZone->shop_id] = (new Utility)->getPriceByDistance($km, $shop, $rate);

                if (
                    Utility::pointInPolygon(data_get($filter, 'address'), $deliveryZone->address)
                    && $orders[$deliveryZone->shop_id] > 0
                ) {
                    return $deliveryZone->shop_id;
                }

                unset($orders[$deliveryZone->shop_id]);

                return null;
            })
                ->reject(fn($data) => empty($data))
                ->toArray();

            // Что бы отсортировать по наименьшей цене нужны значения,
            // далее для фильтра берём только ключи(shop_id) array_keys($orders)
            arsort($orders);

            $shopId = array_key_first($orders);

        }

        return Product::filter($filter)
            ->with([
            'translation' => fn($q) => $q->where('locale', $this->language),
            'stocks' => fn($q) => $q->select('id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon')
                ->with([
                    'bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
                        'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                        'bonus_quantity', 'value', 'type', 'status'
                    ])
                ]),
            'discounts' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),

        ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ->select([
                'id',
                'uuid',
                'category_id',
                'shop_id',
                'brand_id',
                'unit_id',
                'keywords',
                'tax',
                'active',
                'img',
                'min_qty',
                'max_qty',
                'addon',
            ])
            ->where([
                ['shop_id', $shopId],
                ['status',  Product::PUBLISHED],
                ['active',  true],
                ['addon',  false],
            ])
            ->when(data_get($filter, 'category_id'), fn($q, $categoryId) => $q->where('category_id', $categoryId))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsRecommendedPaginate(array $filter): LengthAwarePaginator
    {
        $shopId = data_get($filter, 'shop_id');

        if (data_get($filter, 'address.latitude') && empty($shopId)) {

            $orders = [];

            DeliveryZone::list()->map(function (DeliveryZone $deliveryZone) use ($filter, &$orders) {

                if (!$deliveryZone->shop_id) {
                    return null;
                }

                $shop       = $deliveryZone->shop;

                $location   = data_get($deliveryZone->shop, 'location', []);

                $km         = (new Utility)->getDistance($location, data_get($filter, 'address', []));
                $rate       = data_get($filter, 'currency.rate', 1);

                $orders[$deliveryZone->shop_id] = (new Utility)->getPriceByDistance($km, $shop, $rate);

                if (
                    Utility::pointInPolygon(data_get($filter, 'address'), $deliveryZone->address)
                    && $orders[$deliveryZone->shop_id] > 0
                ) {
                    return $deliveryZone->shop_id;
                }

                unset($orders[$deliveryZone->shop_id]);

                return null;
            })
                ->reject(fn($data) => empty($data))
                ->toArray();

            // Что бы отсортировать по наименьшей цене нужны значения,
            // далее для фильтра берём только ключи(shop_id) array_keys($orders)
            arsort($orders);

            $shopId = array_key_first($orders);

        }

        $ids = !empty($shopId) ? data_get(Cache::get('shop-recommended-ids'), $shopId, []) : [];

        return Product::filter($filter)->with([
            'stock' => fn($q) => $q->select('id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon')
                ->where('quantity', '>', 0)->where('addon', false),
            'translation' => fn($q) => $q->where('locale', $this->language),
            'stock.bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
                'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                'bonus_quantity', 'value', 'type', 'status'
            ]),
            'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
        ])
            ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
            ->select([
                'id',
                'uuid',
                'category_id',
                'img',
                'status',
                'active',
                'addon',
                'shop_id',
                'max_qty',
                'min_qty',
            ])
            ->where([
                ['shop_id', $shopId],
                ['status',  Product::PUBLISHED],
                ['active',  true],
                ['addon',  false],
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->whereIn('id', $ids)
            ->paginate(data_get($filter, 'perPage', 10));
    }
}
