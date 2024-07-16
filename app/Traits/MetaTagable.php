<?php

namespace App\Traits;

use App\Models\MetaTag;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read Collection|MetaTag[] $metaTags
 * @property-read int $meta_tags_count
 */
trait MetaTagable
{

    public function setMetaTags(array $data): void
    {
        $metaTags = data_get($data, 'meta', []);

        if (is_array($metaTags)) {
            $this->metaTags()->delete();
        }

        foreach (is_array($metaTags) ? $metaTags : [] as $value) {

            $this->metaTags()->create([
                'path'          => data_get($value, 'path'),
                'title'         => data_get($value, 'title'),
                'keywords'      => data_get($value, 'keywords'),
                'description'   => data_get($value, 'description'),
                'h1'            => data_get($value, 'h1'),
                'seo_text'      => data_get($value, 'seo_text'),
                'canonical'     => data_get($value, 'canonical'),
                'robots'        => data_get($value, 'robots'),
                'change_freq'   => data_get($value, 'change_freq'),
                'priority'      => data_get($value, 'priority'),
            ]);
        }

    }

    public function metaTags(): MorphMany
    {
        return $this->morphMany(MetaTag::class, 'model');
    }
}

