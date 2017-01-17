<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Vehicle extends BaseModel
{
    use VehicleAttributes;

    protected $with = [
        'photos', 'model', 'model.category', 'model.make', 'condition', 'year', 'color', 'bodyType',
        'door', 'size', 'mileage', 'fuel', 'transmission', 'engine', 'taxBand', 'seller',
        'userFavourite'
    ];

    protected $hidden = [
        'condition', 'year', 'color', 'bodyType', 'door', 'size', 'mileage',
        'fuel', 'transmission', 'engine', 'taxBand', 'seller',
        'userReport', 'userFavourite'
    ];

    protected $appends = [
        'details', 'has_reported', 'has_favourited'
    ];

    /**
     * The model of the vehicle
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function model()
    {
        return $this->belongsTo(Model::class);
    }

    /**
     * The user that is selling the vehicle
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the listing has been paid for.
     *
     * @return bool
     */
    public function getHasPaidAttribute()
    {
        return $this->paid_at != null;
    }

    /**
     * Only select active vehicles.
     *
     * @param Builder $builder
     */
    public function scopeActive(Builder $builder)
    {
        $builder
            ->whereNotNull('paid_at')
            ->where(function(Builder $builder) {
                $builder->whereNull('deactivated_at')->orWhere('deactivated_at', '>', Carbon::now());
            });
    }

    /**
     * Photos of the vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photos()
    {
        return $this->hasMany(VehiclePhoto::class);
    }

    public function userFavourite()
    {
        $userId = Auth::user() ? Auth::user()->id : null;
        return $this->hasOne(Favourite::class)->where('user_id', $userId);
    }

    public function userReport()
    {
        $userId = Auth::user() ? Auth::user()->id : null;
        return $this->hasOne(Report::class)->where('reporter_id', $userId);
    }

    public function getHasFavouritedAttribute()
    {
        return $this->userFavourite !== null;
    }

    public function getHasReportedAttribute()
    {
        return $this->userReport !== null;
    }
}
