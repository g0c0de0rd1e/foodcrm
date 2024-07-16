<?php

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ShopGallery
 *
 * @property int $id
 * @property int $shop_id
 * @property boolean $active
 * @property Shop|null $shop
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLoadableId($value)
 * @method static Builder|self whereLoadableType($value)
 * @method static Builder|self whereMime($value)
 * @method static Builder|self wherePath($value)
 * @method static Builder|self whereSize($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereType($value)
 * @mixin Eloquent
 */
class ShopGallery extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    public $timestamps = false;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
        ;
    }
}
