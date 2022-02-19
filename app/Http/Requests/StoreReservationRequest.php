<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('create', Reservation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'office_id'         => ['required', 'integer'],
            'start_date'        => ['required', 'date:Y-m-d', 'after:tomorrow'],
            'end_date'          => ['required', 'date:Y-m-d', 'after:start_date'],
        ];
    }
}
