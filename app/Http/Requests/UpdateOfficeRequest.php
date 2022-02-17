<?php

namespace App\Http\Requests;

use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user() && Auth::user()->can('update', [$this->office]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title'             => ['filled', 'string'],
            'description'       => ['filled', 'string'],
            'lat'               => ['filled', 'numeric'],
            'lng'               => ['filled', 'numeric'],
            'address_line1'     => ['filled', 'string'],
            'price_per_day'     => ['filled', 'integer', 'min:100'],
            'featured_image_id' => [
                'integer',
                Rule::exists('images', 'id')
                    ->where('imageable_type', 'office')
                    ->where('imageable_id', $this->office->id),
                ],
            'hidden'            => ['boolean'],
            'monthly_discount'  => ['integer', 'min:0'],
            'tags'              => ['array'],
            'tags.*'            => ['integer', Rule::exists('tags', 'id')],
        ];
    }
}
