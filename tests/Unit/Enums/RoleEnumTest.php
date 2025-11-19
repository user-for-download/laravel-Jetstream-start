<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RoleEnum;
use Tests\TestCase;

class RoleEnumTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertSame('admin', RoleEnum::ADMIN->value);
        $this->assertSame('editor', RoleEnum::EDITOR->value);
        $this->assertSame('viewer', RoleEnum::VIEWER->value);
    }

    public function test_get_label_returns_correct_labels(): void
    {
        $this->assertSame('Administrator', RoleEnum::ADMIN->getLabel());
        $this->assertSame('Editor', RoleEnum::EDITOR->getLabel());
        $this->assertSame('Viewer', RoleEnum::VIEWER->getLabel());
    }

    public function test_get_description_returns_correct_descriptions(): void
    {
        $this->assertSame(
            'Administrator users can perform any action.',
            RoleEnum::ADMIN->getDescription()
        );
        $this->assertSame(
            'Editor users have the ability to read, create, and update.',
            RoleEnum::EDITOR->getDescription()
        );
        $this->assertSame(
            'Viewer users have read-only access.',
            RoleEnum::VIEWER->getDescription()
        );
    }

    public function test_get_permissions_returns_correct_permissions_for_admin(): void
    {
        $permissions = RoleEnum::ADMIN->getPermissions();

        $this->assertSame([
            'create',
            'read',
            'update',
            'delete',
        ], $permissions);
    }

    public function test_get_permissions_returns_correct_permissions_for_editor(): void
    {
        $permissions = RoleEnum::EDITOR->getPermissions();

        $this->assertSame([
            'create',
            'read',
            'update',
        ], $permissions);
    }

    public function test_get_permissions_returns_correct_permissions_for_viewer(): void
    {
        $permissions = RoleEnum::VIEWER->getPermissions();

        $this->assertSame(['read'], $permissions);
    }

    public function test_values_returns_all_role_values(): void
    {
        $values = RoleEnum::values();

        $this->assertSame([
            'admin',
            'editor',
            'viewer',
        ], $values);
    }

    public function test_to_select_array_returns_value_label_pairs(): void
    {
        $selectArray = RoleEnum::toSelectArray();

        $this->assertSame([
            'admin' => 'Administrator',
            'editor' => 'Editor',
            'viewer' => 'Viewer',
        ], $selectArray);
    }

    public function test_has_all_expected_cases(): void
    {
        $cases = RoleEnum::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(RoleEnum::ADMIN, $cases);
        $this->assertContains(RoleEnum::EDITOR, $cases);
        $this->assertContains(RoleEnum::VIEWER, $cases);
    }
}
