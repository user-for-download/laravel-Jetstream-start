<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
                @if($team)
                    - {{ $team->name }}
                @endif
            </h2>
            <div class="flex items-center space-x-2">
                {{-- Display Owner Label if the user owns the team --}}
                @if($isTeamOwner)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Owner
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(!$team)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                You don't have a team yet.
                                <a href="{{ route('teams.create') }}" class="font-medium underline">Create your first team</a>
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    {{-- Welcome Card --}}
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6 text-white">
                            <h3 class="text-2xl font-bold">Welcome back, {{ $user->name }}!</h3>
                            <p class="mt-2 text-blue-100">
                                You have {{ count($permissions) }} permissions in {{ $team->name }}
                            </p>
                        </div>
                    </div>

                    {{-- Key Metrics --}}
                    @livewire('dashboard.team-statistics')

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            {{-- Quick Actions --}}
                            @livewire('dashboard.quick-actions')

                            {{-- Recent Activity Timeline --}}
                            @livewire('dashboard.recent-activity')
                        </div>

                        <div class="space-y-6">
                            {{-- Permission Overview --}}
                            @livewire('dashboard.user-permissions')

                            {{-- Team Members List --}}
                            @livewire('dashboard.team-members-widget')
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
