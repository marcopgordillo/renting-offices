<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'lat', 'lng', 'address_line1', 'address_line2', 'approval_status', 'hidden', 'price_per_day', 'monthly_discount',
    ];

    protected $casts = [
        'lat'               => 'decimal:8',
        'lng'               => 'decimal:8',
        'approval_status'   => ApprovalStatus::class,
        'price_per_day'     => 'integer',
        'monthly_discount'  => 'integer',
        'hidden'            => 'boolean',
    ];

    public function scopePublic(Builder $query)
    {
        return $query->where('hidden', false)
                    ->where('approval_status', ApprovalStatus::APPROVED);
    }

    public function scopeNearestTo(Builder $builder, $lat, $lng)
    {
        return $builder
            ->select()
            ->orderByRaw(
                "SQRT(POW(69.1 * (lat - ?), 2) + POW(69.1 * (? - lng) * COS(lat / 57.3), 2))",
                [$lat, $lng]
            );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
