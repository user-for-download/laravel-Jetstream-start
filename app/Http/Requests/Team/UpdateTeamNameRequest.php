<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\DataTransferObjects\Team\UpdateTeamNameDto;
use App\Http\Requests\BaseFormRequest;
use App\Models\Team;

final class UpdateTeamNameRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team &&
            $this->user() !== null &&
            $this->user()->can('update', $team);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): UpdateTeamNameDto
    {
        return UpdateTeamNameDto::fromRequest($this->validated());
    }
}
