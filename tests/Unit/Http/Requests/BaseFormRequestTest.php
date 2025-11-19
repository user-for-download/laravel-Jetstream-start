<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BaseFormRequestTest extends TestCase
{
    public function test_validated_data_returns_validated_array(): void
    {
        $concreteFormRequest = new ConcreteFormRequest();
        $concreteFormRequest->replace(['name' => 'Test', 'extra' => 'value']);

        $validator = ValidatorFacade::make(
            $concreteFormRequest->all(),
            ['name' => ['required', 'string']]
        );

        $concreteFormRequest->setValidator($validator);

        $this->assertEquals(['name' => 'Test'], $concreteFormRequest->validatedData());
    }

    public function test_authorize_returns_true_by_default(): void
    {
        $concreteFormRequest = new ConcreteFormRequest();

        $this->assertTrue($concreteFormRequest->authorize());
    }

    public function test_error_bag_returns_correct_format(): void
    {
        $concreteFormRequest = new ConcreteFormRequest();

        $errorBag = $this->invokeMethod($concreteFormRequest, 'errorBag');

        $this->assertEquals('concreteForm', $errorBag);
    }

    public function test_failed_validation_throws_exception_with_custom_error_bag(): void
    {
        $concreteFormRequest = new ConcreteFormRequest();
        $concreteFormRequest->replace(['name' => '']);

        $validator = ValidatorFacade::make(
            $concreteFormRequest->all(),
            ['name' => ['required']]
        );

        $validator->fails(); // Trigger validation

        try {
            $this->invokeMethod($concreteFormRequest, 'failedValidation', [$validator]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $validationException) {
            $this->assertEquals('concreteForm', $validationException->errorBag);
        }
    }

    public function test_attributes_returns_empty_array_by_default(): void
    {
        $concreteFormRequest = new ConcreteFormRequest();

        $this->assertEquals([], $concreteFormRequest->attributes());
    }

    public function test_messages_returns_empty_array_by_default(): void
    {
        $concreteFormRequest = new ConcreteFormRequest();

        $this->assertEquals([], $concreteFormRequest->messages());
    }

    private function invokeMethod(\Tests\Unit\Http\Requests\ConcreteFormRequest $concreteFormRequest, string $methodName, array $parameters = []): mixed
    {
        $reflectionClass = new \ReflectionClass($concreteFormRequest::class);
        $reflectionMethod = $reflectionClass->getMethod($methodName);

        return $reflectionMethod->invokeArgs($concreteFormRequest, $parameters);
    }
}

// Concrete implementation for testing
class ConcreteFormRequest extends BaseFormRequest
{
    public function toDto(): object
    {
        return (object) $this->validated();
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }
}
