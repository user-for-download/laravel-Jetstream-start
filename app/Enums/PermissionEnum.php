<?php

// ========== app/Enums/PermissionEnum.php ==========

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case CREATE = 'create';
    case READ = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';

    /**
     * Получить человеко-читаемое название разрешения
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::READ => 'Read',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
        };
    }

    /**
     * Получить все значения разрешений
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
