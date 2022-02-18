<?php

namespace App\Http\Controllers\V1;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;
use App\Http\Resources\V1\OfficeResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Enums\ReservationStatus;
use App\Models\User;
use App\Notifications\OfficePendingApproval;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'verified'])->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $offices = Office::query()
                ->public($request)
                ->ownsToUserId($request)
                ->visitor($request)
                ->nearestTo($request)
                ->with(['reservations', 'user', 'images', 'tags'])
                ->withCount(['reservations' =>
                    fn (Builder $builder) => $builder->where('status', ReservationStatus::ACTIVE)])
                ->paginate(20);

        return OfficeResource::collection($offices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOfficeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOfficeRequest $request)
    {
        $office = DB::transaction(function () use($request) {
            $office = Auth::user()->offices()->create(
                [
                    'approval_status'   => ApprovalStatus::PENDING,
                    ...Arr::except($request->validated(), ['tags'])
                ]
            );

            $office->tags()->attach($request->validated('tags'));

            return $office;
        });

        Notification::send(User::Admin()->get(), new OfficePendingApproval($office));

        return OfficeResource::make(
            $office->load(['images', 'tags', 'user'])
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function show(Office $office)
    {
        $office
            ->loadCount(['reservations' =>
                    fn (Builder $builder) => $builder->where('status', ReservationStatus::ACTIVE)])
            ->load(['reservations', 'user', 'images', 'tags']);

        return OfficeResource::make($office);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOfficeRequest  $request
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOfficeRequest $request, Office $office)
    {
        $attributes = $request->validated();

        $office->fill(Arr::except($attributes, ['tags']));

        if ($requiresReview = $office->isDirty(['lat', 'lng', 'price_per_day'])) {
            $office->fill(['approval_status' => ApprovalStatus::PENDING]);
        }

        DB::transaction(function () use($attributes, $office) {
            $office->save();

            if (isset($attributes['tags'])) {
                $office->tags()->sync($attributes['tags']);
            }
        });

        if ($requiresReview) {
            Notification::send(User::Admin()->get(), new OfficePendingApproval($office));
        }

        return OfficeResource::make(
            $office->load(['images', 'tags', 'user'])
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function destroy(Office $office)
    {
        $this->authorize('delete', $office);

        throw_if(
            $office->reservations()->where('status', ReservationStatus::ACTIVE)->exists(),
            ValidationException::withMessages([
                'office' => 'Cannot delete this office with active reservations.'
            ])
        );

        $office->images()->each(function($image) {
            Storage::delete($image->path);
            $image->delete();
        });

        $office->delete();

        return response([], Response::HTTP_NO_CONTENT);
    }
}
