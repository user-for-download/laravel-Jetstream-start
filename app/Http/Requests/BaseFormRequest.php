<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

abstract class BaseFormRequest extends FormRequest
{
    abstract public function toDto(): object;

    public function validatedData(): array
    {
        return $this->validated();
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function errorBag(): string
    {
        return lcfirst(str_replace('Request', '', class_basename($this)));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag());
    }

    public function attributes(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
