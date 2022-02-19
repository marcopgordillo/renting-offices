<?php

namespace App\Http\Requests;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class IndexReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('viewAny', Reservation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status'        => [new Enum(ReservationStatus::class)],
            'from_date'     => ['date', 'required_with:to_date'],
            'to_date'       => ['date', 'required_with:from_date', 'after:from_date'],
            'office_id'     => ['integer'],
            'user_id'       => ['integer'],
        ];
    }
}
