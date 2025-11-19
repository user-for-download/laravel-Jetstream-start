<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Your Permissions') }}</h3>

        {{-- Priority Check: Are they the Owner? --}}
        @teamowner
        <div class="p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">{{ __('Team Owner') }}</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>{{ __('You have full administrative access and control over this team.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @else
            {{-- Not Owner: Show Role & Permission List --}}
            @if($roleName)
                <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-900">{{ $roleName }}</span>
                        <x-role-badge :role="current_team_role()?->key ?? 'member'" />
                    </div>
                    @if($roleDescription)
                        <p class="mt-1 text-xs text-gray-600">{{ $roleDescription }}</p>
                    @endif
                </div>
            @endif

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                @forelse($permissions as $permission)
                    <div class="flex items-center p-2 bg-white border border-gray-100 rounded hover:bg-gray-50 transition shadow-sm" wire:key="perm-{{ $loop->index }}">
                        <svg class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $permission) }}</span>
                    </div>
                @empty
                    <div class="p-4 text-center bg-gray-50 rounded border border-dashed border-gray-200">
                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <p class="text-sm text-gray-500">{{ __('No specific permissions assigned.') }}</p>
                    </div>
                @endforelse
            </div>
            @endteamowner
    </div>
</div>
