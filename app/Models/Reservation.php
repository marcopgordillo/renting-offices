<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Enums\ReservationStatus;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'office_id', 'price', 'status', 'start_date', 'end_date',
    ];

    protected $casts = [
        'price'         => 'integer',
        'status'        => ReservationStatus::class,
        'start_date'    => 'immutable_date',
        'end_date'      => 'immutable_date',
    ];

    public function scopeBetweenDates($query, $from_date, $to_date)
    {
        return $query
                    ->where(fn ($query) =>
                        $query
                            ->whereBetween('start_date', [$from_date, $to_date])
                            ->orWhereBetween('end_date', [$from_date, $to_date])
                            ->orWhere(fn ($query) =>
                                $query->where('start_date', '<', $from_date)
                                    ->where('end_date', '>', $to_date)
                            )
                        );
    }

    public function scopeActiveBetweenDates($query, $from_date, $to_date)
    {
        return $query->whereStatus(ReservationStatus::ACTIVE)
                    ->betweenDates($from_date, $to_date);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
