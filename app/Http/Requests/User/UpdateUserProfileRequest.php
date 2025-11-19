<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\DataTransferObjects\User\UpdateUserProfileDto;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserProfileRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ];
    }

    public function toDto(): UpdateUserProfileDto
    {
        return UpdateUserProfileDto::fromRequest($this->validated());
    }

    public function messages(): array
    {
        return [
            'photo.mimes' => 'The photo must be a file of type: jpg, jpeg, png.',
            'photo.max' => 'The photo must not be larger than 1MB.',
        ];
    }
}
