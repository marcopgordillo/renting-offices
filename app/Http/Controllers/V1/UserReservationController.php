<?php

namespace App\Http\Controllers\V1;

use App\Enums\ApprovalStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Stmt\TryCatch;

class UserReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'verified']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexReservationRequest $request)
    {
        $reservations = Reservation::query()
                                    ->where('user_id', auth()->id())
                                    ->when($request->office_id, fn ($query) =>
                                        $query->where('office_id', $request->office_id)
                                    )
                                    ->when($request->status, fn ($query) =>
                                        $query->where('status', $request->status)
                                    )
                                    ->when($request->from_date && $request->to_date, fn ($query) =>
                                        $query->betweenDates($request->from_date, $request->to_date)
                                    )
                                    ->with(['office.featuredImage'])
                                    ->paginate(20);

        return ReservationResource::collection($reservations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreReservationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $office = Office::findOrFail($request->office_id);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages([
                'office_id' => 'Invalid office_id'
            ]);
        }

        throw_if($office->user_id === auth()->id(),
            ValidationException::withMessages([
                'office_id' => 'You cannot make a reservation in your own office'
            ])
        );

        throw_if($office->hidden || $office->approval_status === ApprovalStatus::PENDING,
            ValidationException::withMessages([
                'office_id'    => 'You cannot make a reservation on a hidden office'
            ])
        );

        $reservation = Cache::lock("reservations_office_{$office->id}", 10)
                            ->block(3, function() use($request, $office) {

            throw_if($office->reservations()
                    ->whereStatus(ReservationStatus::ACTIVE)
                    ->activeBetweenDates($request->start_date, $request->end_date)->exists(),
                ValidationException::withMessages([
                    'start_date' => 'You cannot make a reservation during this time.'
                ])
            );

            $numberOfDays = Carbon::parse($request->end_date)->endOfDay()->diffInDays(
                Carbon::parse($request->start_date)->startOfDay()
            ) + 1;

            $price = $numberOfDays * $office->price_per_day;

            if ($numberOfDays >= 28 && $office->monthly_discount) {
                $price = $price - ($price * $office->monthly_discount / 100);
            }

            return Reservation::create([
                'user_id'       => auth()->id(),
                'office_id'     => $office->id,
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'status'        => ReservationStatus::ACTIVE,
                'price'         => $price,
                'wifi_password' => str()->random(),
            ]);
        });

        Notification::send(auth()->user(), new NewUserReservation($reservation));
        Notification::send($office->user, new NewHostReservation($reservation));

        return ReservationResource::make(
            $reservation->load('office', 'user')
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function show(Reservation $reservation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateReservationRequest  $request
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reservation $reservation)
    {
        //
    }

    public function cancel(Reservation $reservation)
    {
        $this->authorize('cancel', $reservation);

        throw_if($reservation->user_id !== auth()->id()
                || $reservation->status != ReservationStatus::ACTIVE
                || $reservation->start_date < today()->toDateString(),
            ValidationException::withMessages([
                'reservation'   => 'You cannot cancel this reservation'
            ])
        );

        $reservation->update([
            'status'    => ReservationStatus::CANCELLED,
        ]);

        return response('', Response::HTTP_NO_CONTENT);
    }
}
