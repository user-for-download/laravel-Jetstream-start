<?php

// ========== app/Enums/RoleEnum.php ==========

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * Получить человеко-читаемое название роли
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
        };
    }

    /**
     * Получить описание роли
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator users can perform any action.',
            self::EDITOR => 'Editor users have the ability to read, create, and update.',
            self::VIEWER => 'Viewer users have read-only access.',
        };
    }

    /**
     * Получить разрешения для роли
     *
     * @return array<string>
     */
    public function getPermissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                PermissionEnum::CREATE->value,
                PermissionEnum::READ->value,
                PermissionEnum::UPDATE->value,
                PermissionEnum::DELETE->value,
            ],
            self::EDITOR => [
                PermissionEnum::CREATE->value,
                PermissionEnum::READ->value,
                PermissionEnum::UPDATE->value,
            ],
            self::VIEWER => [
                PermissionEnum::READ->value,
            ],
        };
    }

    /**
     * Получить все значения ролей
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Получить все роли в виде массива для select
     *
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $result = [];
        foreach (self::cases() as $role) {
            $result[$role->value] = $role->getLabel();
        }

        return $result;
    }
}
