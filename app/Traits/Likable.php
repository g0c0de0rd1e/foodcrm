<?php

namespace App\Traits;

use App\Models\Like;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Likable
{

    public function liked()
    {
        $like = $this->likes()->firstWhere('user_id', auth('sanctum')->id());

        if ($like) {
            $like->delete();
        } else {
            $this->likes()->create([
                'user_id' =>  auth('sanctum')->id()
            ]);
        }
    }
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }
}