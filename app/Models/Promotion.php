<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Promotion extends BaseModel
{
    protected $casts = [
        'valid_until' => 'datetime'
    ];

    /**
     * Only active promotions.
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeActive(Builder $builder)
    {
        return $builder->where('valid_until', '>=', Carbon::now()->startOfDay());
    }

    /**
     * User who the free listings belong to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
