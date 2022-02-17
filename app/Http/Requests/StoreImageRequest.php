<?php

namespace App\Http\Requests;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return Auth::user()->can('create', [Image::class, $this->office]);
        return Auth::user()->can('update', [Office::class, $this->office]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'image' => ['image', 'max:5000'],
        ];
    }
}
