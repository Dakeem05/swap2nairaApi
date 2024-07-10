<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RequestCreationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_id' => ['required', 'exists:cards,id'],
            'number' => ['required', 'numeric', 'min:20'],
            'category' => ['required', 'string'],
            'images' => ['array', 'required_unless:category,ecode,both', ],
            'images.*' => ['mimes:jpeg,png,jpg,svg,webp,bmp','max:2048', 'required_unless:category,ecode,both', ],
            'ecodes' => ['array', 'required_unless:category,physical,both', ],
            'ecodes.*' => ['string', 'required_unless:category,physical,both',]
        ];
    }

    public function failedValidation (Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors()
        ], 400));
    }
}
