<?php

namespace App\Http\Requests;

use App\Enums\ApprovalStatus;
use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('create', Office::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title'         => ['required', 'string'],
            'description'   => ['required', 'string'],
            'lat'           => ['required', 'numeric'],
            'lng'           => ['required', 'numeric'],
            'address_line1' => ['required', 'string'],
            'hidden'        => ['boolean'],
            'price_per_day' => ['required', 'integer', 'min:100'],
            'monthly_discount'  => ['integer', 'min:0'],
            'tags'          => ['array'],
            'tags.*'        => ['integer', Rule::exists('tags', 'id')],
        ];
    }
}
