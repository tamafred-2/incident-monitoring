<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100', new PersonName],
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_name' => ['nullable', 'string', 'max:100', new PersonName],
            'extension' => ['nullable', 'string', 'max:20', new PersonName],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()->user_id, 'user_id'),
            ],
        ];
    }
}
