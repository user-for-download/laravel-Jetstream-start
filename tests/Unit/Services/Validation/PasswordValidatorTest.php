<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Validation;

use App\Services\Validation\PasswordValidator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordValidatorTest extends TestCase
{
    private PasswordValidator $passwordValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordValidator = new PasswordValidator();
    }

    public function test_rules_returns_array_with_confirmation(): void
    {
        $rules = $this->passwordValidator->rules();

        $this->assertIsArray($rules);
        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('confirmed', $rules);
    }

    public function test_rules_without_confirmation(): void
    {
        $rules = $this->passwordValidator->rulesWithoutConfirmation();

        $this->assertIsArray($rules);
        $this->assertNotContains('confirmed', $rules);
    }

    public function test_password_rule_returns_password_instance(): void
    {
        $password = $this->passwordValidator->passwordRule();

        $this->assertInstanceOf(Password::class, $password);
    }
}
