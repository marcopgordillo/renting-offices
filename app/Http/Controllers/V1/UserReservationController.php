<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
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

        if ($office->user_id === auth()->id()) {
            throw ValidationException::withMessages([
                'office_id' => 'You cannot make a reservation in your own office'
            ]);
        }
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
}
