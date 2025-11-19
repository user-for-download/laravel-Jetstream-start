<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\View\View;
use Livewire\Component;

class QuickActions extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard.quick-actions');
    }
}
