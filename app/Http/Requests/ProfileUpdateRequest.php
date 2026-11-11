<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->getKey()),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'mobile' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^\+?[0-9\-\s]{10,20}$/'
            ],
            'city' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z .\'-]+$/'],
            'state' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z .\'-]+$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif'],
        ];
    }
}
