<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PermissionEnum;
use Tests\TestCase;

class PermissionEnumTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertSame('create', PermissionEnum::CREATE->value);
        $this->assertSame('read', PermissionEnum::READ->value);
        $this->assertSame('update', PermissionEnum::UPDATE->value);
        $this->assertSame('delete', PermissionEnum::DELETE->value);
    }

    public function test_get_label_returns_correct_labels(): void
    {
        $this->assertSame('Create', PermissionEnum::CREATE->getLabel());
        $this->assertSame('Read', PermissionEnum::READ->getLabel());
        $this->assertSame('Update', PermissionEnum::UPDATE->getLabel());
        $this->assertSame('Delete', PermissionEnum::DELETE->getLabel());
    }

    public function test_values_returns_all_permission_values(): void
    {
        $values = PermissionEnum::values();

        $this->assertSame([
            'create',
            'read',
            'update',
            'delete',
        ], $values);
    }

    public function test_has_all_expected_cases(): void
    {
        $cases = PermissionEnum::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(PermissionEnum::CREATE, $cases);
        $this->assertContains(PermissionEnum::READ, $cases);
        $this->assertContains(PermissionEnum::UPDATE, $cases);
        $this->assertContains(PermissionEnum::DELETE, $cases);
    }
}
