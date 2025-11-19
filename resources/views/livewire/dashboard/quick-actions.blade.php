<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Quick Actions') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Create Project Action --}}
            @teamcan('create')
            <button type="button" class="group w-full flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-blue-500 p-3 group-hover:bg-blue-600 transition">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 text-left">
                    <div class="text-sm font-medium text-gray-900">{{ __('Create New Project') }}</div>
                    <div class="text-xs text-gray-500">{{ __('Start a new project for your team') }}</div>
                </div>
            </button>
            @endteamcan

            {{-- Manage Team Action --}}
            @teamadmin
            <a href="{{ route('teams.show', current_team()) }}" class="group flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-green-500 p-3 group-hover:bg-green-600 transition">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ __('Manage Team') }}</div>
                    <div class="text-xs text-gray-500">{{ __('Manage members and settings') }}</div>
                </div>
            </a>
            @endteamadmin

            {{-- View Reports Action --}}
            @teamcan('read')
            <button type="button" class="group w-full flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition focus:outline-none focus:ring-2 focus:ring-purple-500">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-purple-500 p-3 group-hover:bg-purple-600 transition">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 text-left">
                    <div class="text-sm font-medium text-gray-900">{{ __('View Reports') }}</div>
                    <div class="text-xs text-gray-500">{{ __('Check team performance') }}</div>
                </div>
            </button>
            @endteamcan

            {{-- Edit Profile Action --}}
            <a href="{{ route('profile.show') }}" class="group flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-yellow-500 p-3 group-hover:bg-yellow-600 transition">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ __('Edit Profile') }}</div>
                    <div class="text-xs text-gray-500">{{ __('Update your account settings') }}</div>
                </div>
            </a>
        </div>
    </div>
</div>
