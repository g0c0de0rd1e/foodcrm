<?php

namespace App\Traits;

use App\Models\Stock;
use App\Services\ProductService\ProductService;
use App\Services\ProjectService\ProjectService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Countable
{
    public function addInStock(array $data): void
    {
        $this->stocks()->delete();

        foreach (data_get($data, 'extras', []) as $item) {

            /** @var Stock $stock */

            $stock = $this->stocks()->create([
                'price'     => data_get($item, 'price'),
                'quantity'  => data_get($item, 'quantity'),
                'addon'     => $this->addon
            ]);

            if (is_array(data_get($item, 'ids')) && count(data_get($item, 'ids')) > 0) {
                $stock->stockExtras()->sync(data_get($item, 'ids'));
            }

            if (is_array(data_get($item, 'addons')) && count(data_get($item, 'addons')) > 0) {
                (new ProductService)->syncAddons($stock, data_get($item, 'addons'));
            }


        }

    }

    public function stocks(): MorphMany
    {
        return $this->morphMany(Stock::class, 'countable');
    }
}
