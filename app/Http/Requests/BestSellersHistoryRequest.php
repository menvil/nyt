<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\IsbnRule;

class BestSellersHistoryRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'author' => 'nullable|string|max:255',
            'isbn' => 'nullable|array',
            'isbn.*' => ['nullable', 'string', new IsbnRule()],
            'title' => 'nullable|string|max:255',
            'offset' => 'nullable|integer|min:0|multiple_of:20',
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'offset.multiple_of' => 'The offset must be a multiple of 20.',
            'isbn.*' . '.' . 'isbn_rule' => 'Each ISBN must be a valid 10 or 13 digit number.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'isbn.*' => 'ISBN',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Invalid request parameters',  // Customized message
                'errors' => $validator->errors()
            ], 422)
        );
    }
} 