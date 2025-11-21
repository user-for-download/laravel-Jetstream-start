<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\RoleEnum;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RoleBadge extends Component
{
    public string $color;

    public string $label;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string|RoleEnum|null $role = null,
        ?string $color = null
    ) {
        // Обработка роли
        if ($role instanceof RoleEnum) {
            $this->label = $role->getLabel();
            $roleValue = $role->value;
        } elseif (is_string($role)) {
            $roleEnum = RoleEnum::tryFrom($role);
            $this->label = $roleEnum?->getLabel() ?? ucfirst($role);
            $roleValue = $role;
        } else {
            $this->label = 'Unknown';
            $roleValue = 'unknown';
        }

        // Автоматическое определение цвета
        $this->color = $color ?? $this->getColorForRole($roleValue);
    }

    /**
     * Получить цвет для роли
     */
    private function getColorForRole(string $role): string
    {
        return match ($role) {
            RoleEnum::ADMIN->value, 'admin', 'owner' => 'red',
            RoleEnum::EDITOR->value, 'editor' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.role-badge');
    }
}
