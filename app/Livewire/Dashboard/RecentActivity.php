<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\ActivityLogEnum;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class RecentActivity extends Component
{
    public Collection $activities;

    public function mount(): void
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        $this->activities = Activity::query()
            ->with(['causer', 'subject'])
            ->where(function ($query) use ($user): void {
                $query->where('causer_id', $user->id)
                    ->where('causer_type', $user::class);
            })
            ->when($team, function ($query) use ($team): void {
                $query->orWhere(function ($q) use ($team): void {
                    $q->where('subject_id', $team->id)
                        ->where('subject_type', $team::class);
                });
            })
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (Activity $activity): array => $this->formatActivity($activity));
    }

    private function formatActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->description,
            'description' => $this->generateDescription($activity),
            'user' => $activity->causer->name ?? 'System',
            'created_at' => $activity->created_at,
            'icon' => $this->getIconForEvent($activity->description),
            'color' => $this->getColorForEvent($activity->description),
        ];
    }

    private function generateDescription(Activity $activity): string
    {
        $props = $activity->properties;
        $subjectName = $activity->subject->name ?? 'Unknown';

        return match ($activity->description) {
            // Use ->value for comparison
            ActivityLogEnum::MEMBER_ADDED->value => sprintf(
                'Added %s as %s',
                $props['member_email'] ?? 'a user',
                $props['role'] ?? 'member'
            ),
            ActivityLogEnum::MEMBER_REMOVED->value => sprintf('Removed member %s', $props['member_email'] ?? ''),
            ActivityLogEnum::INVITATION_SENT->value => sprintf('Invited %s', $props['invited_email'] ?? ''),
            ActivityLogEnum::TEAM_CREATED->value => sprintf('Created team "%s"', $subjectName),
            ActivityLogEnum::OWNERSHIP_TRANSFERRED->value => 'Transferred team ownership',
            ActivityLogEnum::ROLE_UPDATED->value => sprintf('Updated role for %s to %s', $props['member_email'] ?? 'user', $props['new_role'] ?? ''),
            ActivityLogEnum::INVITATION_CANCELLED->value => sprintf('Cancelled invitation for %s', $props['invited_email'] ?? ''),
            ActivityLogEnum::TEAM_UPDATED->value => 'Updated details',
            ActivityLogEnum::CREATED->value => 'Created record',
            default => $activity->description,
        };
    }

    private function getIconForEvent(string $event): string
    {
        return match ($event) {
            ActivityLogEnum::MEMBER_ADDED->value => 'user-add',
            ActivityLogEnum::MEMBER_REMOVED->value => 'user-remove',
            ActivityLogEnum::TEAM_CREATED->value,
            ActivityLogEnum::CREATED->value => 'folder-add',
            ActivityLogEnum::OWNERSHIP_TRANSFERRED->value => 'key',
            ActivityLogEnum::ROLE_UPDATED->value => 'shield-check',
            default => 'clipboard-list',
        };
    }

    private function getColorForEvent(string $event): string
    {
        return match ($event) {
            ActivityLogEnum::MEMBER_ADDED->value,
            ActivityLogEnum::TEAM_CREATED->value => 'green',
            ActivityLogEnum::MEMBER_REMOVED->value => 'red',
            ActivityLogEnum::OWNERSHIP_TRANSFERRED->value => 'purple',
            ActivityLogEnum::ROLE_UPDATED->value => 'blue',
            default => 'gray',
        };
    }

    public function render(): View
    {
        return view('livewire.dashboard.recent-activity');
    }
}
