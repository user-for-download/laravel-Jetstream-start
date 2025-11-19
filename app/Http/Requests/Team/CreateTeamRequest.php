<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\DataTransferObjects\Team\CreateTeamDto;
use App\Http\Requests\BaseFormRequest;
use Laravel\Jetstream\Jetstream;

final class CreateTeamRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null &&
            $this->user()->can('create', Jetstream::newTeamModel());
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): CreateTeamDto
    {
        return CreateTeamDto::fromRequest($this->validated());
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a team name.',
            'name.max' => 'The team name must not exceed 255 characters.',
        ];
    }
}
