<?php

namespace App\Services\ProductService;

use App\Helpers\ResponseError;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductProperties;
use App\Models\Stock;
use App\Models\Tag;
use App\Services\CoreService;
use App\Services\Interfaces\ProductServiceInterface;
use App\Traits\SetTranslations;
use DB;
use Exception;
use Str;
use Throwable;

class ProductService extends CoreService implements ProductServiceInterface
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            if (
                !empty(data_get($data, 'category_id')) &&
                $this->checkIsParentCategory(data_get($data, 'category_id'))
            ) {
                return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => 'category is parent'];
            }

            if (data_get($data, 'addon') && data_get($data, 'addons.*')) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_501,
                    'message'   => 'You can`t attach products for addon'
                ];
            }

            /** @var Product $product */
            if (data_get($data, 'min_qty') > 1000000) {
                data_set($data, 'min_qty',1000000);
            }

            if (data_get($data, 'max_qty') > 1000000) {
                data_set($data, 'max_qty',1000000);
            }

            $product = $this->model()->create($data);

            $this->setTranslations($product, $data);

            if (data_get($data, 'meta')) {
                $product->setMetaTags($data);
            }

            if (data_get($data, 'images.0')) {
                $product->update(['img' => data_get($data, 'images.0')]);
                $product->uploads(data_get($data, 'images'));
            }

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $product->loadMissing([
                    'translations',
                    'metaTags',
                    'stocks.addons',
                    'stocks.addons.addon.translation' => fn($q) => $q->where('locale', $this->language),
                ])
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'code' => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function update(string $uuid, array $data): array
    {
        try {

            if (
                !empty(data_get($data, 'category_id')) &&
                $this->checkIsParentCategory(data_get($data, 'category_id'))
            ) {
                return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => 'category is parent'];
            }

            if (data_get($data, 'addon') && data_get($data, 'addons.*')) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_501,
                    'message'   => 'You can`t attach products for addon'
                ];
            }

            /** @var Product $product */
            $product = $this->model()->firstWhere('uuid', $uuid);

            if (empty($product)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            if (data_get($data, 'min_qty') &&
                data_get($data, 'max_qty') &&
                data_get($data, 'min_qty') > data_get($data, 'max_qty')
            ) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_400,
                    'message'   => 'max qty must be more than min qty'
                ];
            }

            if (data_get($data, 'min_qty') > 1000000) {
                data_set($data, 'min_qty',1000000);
            }

            if (data_get($data, 'max_qty') > 1000000) {
                data_set($data, 'max_qty',1000000);
            }

            $product->update($data);

            $this->setTranslations($product, $data);

            if (data_get($data, 'meta')) {
                $product->setMetaTags($data);
            }

            if (data_get($data, 'images.0')) {
                $product->galleries()->delete();
                $product->update([ 'img' => data_get($data, 'images.0') ]);
                $product->uploads(data_get($data, 'images'));
            }

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $product->loadMissing([
                    'translations',
                    'metaTags',
                    'stocks.addons',
                    'stocks.addons.addon.translation' => fn($q) => $q->where('locale', $this->language),
                ])
            ];
        } catch (Throwable $e) {
            return [
                'status'    => false,
                'code'      => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message'   => $e->getMessage()
            ];
        }
    }

    public function parentSync(array $data): array
    {
        $errorIds = [];

        foreach (data_get($data, 'products') as $parentId) {

            try {
                $parentId = (int)$parentId;

                DB::transaction(function () use ($parentId, $data) {

                    /** @var Product $parent */

                    $parent = Product::with([
                        'translations',
                        'discounts',
                        'tags.translations',
                        'metaTags',
                        'galleries',
                        'properties',

                        'stocks.stockExtras',
                        'stocks.addons.addon.stock',
                        'stocks.addons.addon.properties',
                        'stocks.addons.addon.translations',
                        'stocks.addons.addon.discounts',
                        'stocks.addons.addon.tags.translations',
                        'stocks.addons.addon.metaTags',
                        'stocks.addons.addon.galleries',
                        'stocks.addons.addon.properties',
                    ])->find($parentId);

                    if (!empty($parent->parent_id)) {
                        throw new Exception('product is child');
                    }

                    $clone = $parent->replicate();

                    $clone['parent_id']  = $parent->id;
                    $clone['shop_id']    = data_get($data, 'shop_id');
                    $clone['uuid']       = Str::uuid();
                    $clone['deleted_at'] = null;

                    $clone = Product::withTrashed()->updateOrCreate([
                        'parent_id' => $parent->id,
                        'shop_id'   => data_get($data, 'shop_id'),
                    ], $clone->getAttributes());

                    $translations   = $parent->translations;
                    $stocks         = $parent->stocks;
                    $discounts      = $parent->discounts;
                    $tags           = $parent->tags;
                    $metaTags       = $parent->metaTags;//?->toArray();
                    $galleries      = $parent->galleries;
                    $properties     = $parent->properties;

                    foreach ($translations as $translation) {

                        $clone->translations()->updateOrCreate([
                            'locale'        => $translation->locale,
                            'product_id'    => $clone->id,
                        ], [
                            'locale'        => $translation->locale,
                            'product_id'    => $clone->id,
                            'title'         => $translation->title,
                            'description'   => $translation->description,
                            'deleted_at'    => null,
                        ]);

                    }

                    foreach ($metaTags as $metaTag) {

                        $clone->metaTags()->updateOrCreate([
                            'model_id'   => $clone->id,
                            'model_type' => get_class($clone)
                        ], [
                            'path'          => data_get($metaTag, 'path'),
                            'title'         => data_get($metaTag, 'title'),
                            'keywords'      => data_get($metaTag, 'keywords'),
                            'description'   => data_get($metaTag, 'description'),
                            'h1'            => data_get($metaTag, 'h1'),
                            'seo_text'      => data_get($metaTag, 'seo_text'),
                            'canonical'     => data_get($metaTag, 'canonical'),
                            'robots'        => data_get($metaTag, 'robots'),
                            'change_freq'   => data_get($metaTag, 'change_freq'),
                            'priority'      => data_get($metaTag, 'priority'),
                        ]);

                    }

                    $clone->galleries()->delete();

                    foreach ($galleries as $gallery) {

                        $newGallery = $gallery->toArray();
                        $newGallery['loadable_id'] = $clone->id;
                        $newGallery['loadable_type'] = get_class($clone);

                        $clone->galleries()->create($newGallery);
                    }

                    foreach ($discounts as $discount) {
                        ProductDiscount::updateOrCreate([
                            'discount_id' => $discount->id,
                            'product_id'  => $clone->id
                        ], [
                            'deleted_at' => null
                        ]);
                    }

                    foreach ($tags as $tag) {

                        /** @var Tag $newTag */

                        $newTag = $clone->tags()->updateOrCreate([
                            'product_id' => $clone->id
                        ], [
                            'active'     => $tag->active,
                            'deleted_at' => null,
                        ]);

                        foreach ($tag->translations as $translation) {
                            $newTag->translations()->updateOrCreate([
                                'locale'        => $translation->locale,
                                'title'         => $translation->title,
                                'description'   => $translation->description,
                                'deleted_at'    => null,
                            ]);
                        }

                    }

                    foreach ($properties as $property) {
                        ProductProperties::updateOrCreate([
                            'product_id' => $clone->id,
                            'locale'     => $property->locale,
                            'key'        => $property->key,
                        ], [
                            'value'      => $property->value,
                        ]);
                    }

                    foreach ($stocks as $stock) {

                        if (!empty($stock->parent_id)) {
                            continue;
                        }

                        /** @var Stock $clonedStock */
                        $clonedStock = $clone->stocks()->updateOrCreate([
                            'countable_type' => get_class($clone),
                            'countable_id'   => $clone->id,
                            'parent_id'      => $stock->id,
                        ], [
                            'price'          => $stock?->price,
                            'quantity'       => $stock?->quantity,
                            'addon'          => $stock?->addon,
                            'deleted_at'     => null,
                        ]);

                        $extras = $stock?->stockExtras?->pluck('id')?->toArray();

                        $clonedStock->stockExtras()->sync(is_array($extras) ? $extras : []);

                        $addons = $stock->addons;

                        if (empty($addons)) {
                            continue;
                        }

                        foreach ($addons as $addon) {

                            $addonClone = $addon->addon->replicate();

                            $addonClone['parent_id']  = $addon->addon->id;
                            $addonClone['shop_id']    = data_get($data, 'shop_id');
                            $addonClone['uuid']       = Str::uuid();
                            $addonClone['deleted_at'] = null;

                            $addonClone = Product::withTrashed()->updateOrCreate([
                                'parent_id' => $addonClone['parent_id'],
                                'shop_id'   => $addonClone['shop_id'],
                            ], $addonClone->getAttributes());

                            $clonedStock->addons()->updateOrCreate([
                                'stock_id' => $clonedStock->id,
                                'addon_id' => $addonClone->id
                            ]);

                            $translations   = $addon->addon->translations;
                            $addonStock     = $addon->addon->stock;
                            $discounts      = $addon->addon->discounts;
                            $tags           = $addon->addon->tags;
                            $metaTags       = $addon->addon->metaTags;//?->toArray();
                            $galleries      = $addon->addon->galleries;
                            $properties     = $addon->addon->properties;

                            foreach ($translations as $translation) {

                                $addonClone->translations()->updateOrCreate([
                                    'locale'        => $translation->locale,
                                    'product_id'    => $addonClone->id,
                                ], [
                                    'locale'        => $translation->locale,
                                    'product_id'    => $addonClone->id,
                                    'title'         => $translation->title,
                                    'description'   => $translation->description,
                                    'deleted_at'    => null,
                                ]);

                            }

                            foreach ($metaTags as $metaTag) {

                                $addonClone->metaTags()->updateOrCreate([
                                    'model_id'   => $addonClone->id,
                                    'model_type' => get_class($addonClone)
                                ], [
                                    'path'          => data_get($metaTag, 'path'),
                                    'title'         => data_get($metaTag, 'title'),
                                    'keywords'      => data_get($metaTag, 'keywords'),
                                    'description'   => data_get($metaTag, 'description'),
                                    'h1'            => data_get($metaTag, 'h1'),
                                    'seo_text'      => data_get($metaTag, 'seo_text'),
                                    'canonical'     => data_get($metaTag, 'canonical'),
                                    'robots'        => data_get($metaTag, 'robots'),
                                    'change_freq'   => data_get($metaTag, 'change_freq'),
                                    'priority'      => data_get($metaTag, 'priority'),
                                ]);

                            }

                            $addonClone->galleries()->delete();

                            foreach ($galleries as $gallery) {

                                $newGallery = $gallery->toArray();
                                $newGallery['loadable_id']   = $addonClone->id;
                                $newGallery['loadable_type'] = get_class($addonClone);

                                $addonClone->galleries()->create($newGallery);
                            }

                            foreach ($discounts as $discount) {
                                ProductDiscount::updateOrCreate([
                                    'discount_id' => $discount->id,
                                    'product_id'  => $addonClone->id
                                ], [
                                    'deleted_at' => null
                                ]);
                            }

                            foreach ($tags as $tag) {

                                /** @var Tag $newTag */

                                $newTag = $addonClone->tags()->updateOrCreate([
                                    'product_id' => $addonClone->id
                                ], [
                                    'active'     => $tag->active,
                                    'deleted_at' => null,
                                ]);

                                foreach ($tag->translations as $translation) {
                                    $newTag->translations()->updateOrCreate([
                                        'locale'        => $translation->locale,
                                        'title'         => $translation->title,
                                        'description'   => $translation->description,
                                        'deleted_at'    => null,
                                    ]);
                                }

                            }

                            foreach ($properties as $property) {
                                ProductProperties::updateOrCreate([
                                    'product_id' => $addonClone->id,
                                    'locale'     => $property->locale,
                                    'key'        => $property->key,
                                ], [
                                    'value'      => $property->value,
                                ]);
                            }

                            if (!empty($addonStock->parent_id)) {
                                continue;
                            }

                            if (!$addonStock?->id) {
                                continue;
                            }

                            /** @var Stock $addonClonedStock */
                            $addonClonedStock = $addonClone->stocks()->updateOrCreate([
                                'countable_type' => get_class($addonClone),
                                'countable_id'   => $addonClone->id,
                                'parent_id'      => $addonStock->id,
                            ], [
                                'price'          => $addonStock->price,
                                'quantity'       => $addonStock->quantity,
                                'addon'          => $addonStock->addon,
                                'deleted_at'     => null,
                            ]);

                            $addonExtras = $addonStock?->stockExtras?->pluck('id')?->toArray();

                            $addonClonedStock->stockExtras()->sync(is_array($addonExtras) ? $addonExtras : []);

                        }
                    }

                });
            } catch (Throwable $e) {
                $errorIds[] = [
                    'id'        => $parentId,
                    'message'   => $e->getMessage()
                ];
            }

        }

        if (count($errorIds) > 0) {
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'data' => $errorIds];
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'message' => ResponseError::NO_ERROR];
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     *
     * @return array
     */
    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $products = Product::whereIn('id', $ids)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        $errorIds = [];

        foreach ($products as $product) {
            try {
                /** @var Product $product */
                $product->children()->delete();
                $product->delete();
            } catch (Throwable $e) {
                if (!empty($e->getMessage())) { // this if only for vercel test demo
                    $errorIds[] = $product->id;
                }
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return ['status' => false, 'code' => ResponseError::ERROR_505, 'message' => implode(', ', $errorIds)];
    }

    /**
     * @param Stock $stock
     * @param array $ids
     * @return array
     */
    public function syncAddons(Stock $stock, array $ids): array
    {
        $errIds = [];

        if (count($ids) === 0) {
            return $errIds;
        }

        try {

            $stock->addons()->delete();

            foreach ($ids as $id) {

                /** @var Product $product */
                $product = Product::with('stock')->where('id', $id)->first();

                if (
                    data_get($product,'stock.addon') !== 1 ||
                    $product->shop_id !== $stock->countable?->shop_id ||
                    $product->stock?->bonus
                ) {
                    $errIds[] = $id;
                    continue;
                }

                $stock->addons()->create([
                    'addon_id' => $id
                ]);

            }

        } catch (Throwable $e) {

            $this->error($e);
            $errIds = $ids;
        }

        return $errIds;
    }

    private function checkIsParentCategory(int $categoryId): bool
    {
        $parentCategory = Category::firstWhere('parent_id', $categoryId);

        return !!data_get($parentCategory, 'id');
    }
}
