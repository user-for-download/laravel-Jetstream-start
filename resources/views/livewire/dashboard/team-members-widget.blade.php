<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Team Members') }}</h3>
            <span class="text-sm text-gray-500">{{ $totalMembers }} {{ __('total') }}</span>
        </div>

        <div class="space-y-3">
            @forelse($members as $member)
                <div class="flex items-center" wire:key="member-{{ $member->id }}">
                    <img class="h-8 w-8 rounded-full object-cover"
                         src="{{ $member->profile_photo_url }}"
                         alt="{{ $member->name }}">
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $member->name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">{{ $member->email }}</p>
                    </div>

                    {{-- Safe navigation for role badge --}}
                    @if(current_team() && $member->teamRole(current_team()))
                        <div class="flex-shrink-0 ml-2">
                            <x-role-badge :role="$member->teamRole(current_team())?->key" />
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center py-2">{{ __('No members found.') }}</p>
            @endforelse
        </div>

        @if($totalMembers > 5)
            <div class="mt-4 text-center">
                <a href="{{ route('teams.show', current_team()) }}"
                   class="text-sm text-blue-600 hover:text-blue-800 transition">
                    {{ __('View all :count members', ['count' => $totalMembers]) }} →
                </a>
            </div>
        @endif

        @teamadmin
        <div class="mt-4 pt-4 border-t border-gray-200">
            <a href="{{ route('teams.show', current_team()) }}"
               class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm shadow-sm">
                {{ __('Manage Team Members') }}
            </a>
        </div>
        @endteamadmin
    </div>
</div>
