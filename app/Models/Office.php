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
use Illuminate\Http\Request;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'lat', 'lng', 'address_line1', 'address_line2', 'approval_status', 'hidden', 'price_per_day', 'monthly_discount', 'featured_image_id',
    ];

    protected $casts = [
        'lat'               => 'decimal:8',
        'lng'               => 'decimal:8',
        'approval_status'   => ApprovalStatus::class,
        'price_per_day'     => 'integer',
        'monthly_discount'  => 'integer',
        'hidden'            => 'boolean',
    ];

    /**
     *  If the owner can see all his own offices
     */
    public function scopePublic(Builder $query, Request $request)
    {
        return $query
                ->when($request->user_id && auth()->user() && $request->user_id == auth()->id(),
                    fn (Builder $builder) => $builder,
                    fn (Builder $builder) => $builder->where('approval_status', ApprovalStatus::APPROVED)
                                                    ->where('hidden', false)
                );
    }

    /**
     * The request has a user_id param, returns all offices from from this user
     */
    public function scopeOwnsToUserId(Builder $query, Request $request)
    {
        return $query
                ->when($request->user_id, fn (Builder $builder) => $builder->whereUserId($request->user_id));
    }

    /**
     * Query the offices which has a reservation with a owner `visitor_id`
     */
    public function scopeVisitor(Builder $query, Request $request)
    {
        return $query
                ->when($request->visitor_id,
                    fn (Builder $builder) =>
                        $builder->whereRelation('reservations', 'user_id', '=', $request->visitor_id));
    }

    /**
     * If the request has lat and lng params, returns the offices ordered by nearest by distance
     */
    public function scopeNearestTo(Builder $builder, Request $request)
    {
        return $builder
                ->when($request->lat && $request->lng,
                    fn (Builder $builder) => $this->selectNearestOffices($builder, $request->lat, $request->lng),
                    fn (Builder $builder) => $builder->orderBy('id', 'DESC')
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

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'featured_image_id');
    }

    private function selectNearestOffices(Builder $builder, $lat, $lng)
    {
        return $builder
                    ->select()
                    ->orderByRaw(
                        "SQRT(POW(69.1 * (lat - ?), 2) + POW(69.1 * (? - lng) * COS(lat / 57.3), 2))",
                        [$lat, $lng]
                    );
    }
}
