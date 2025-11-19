<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-mark class="block h-9 w-auto"/>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-4">
                <!-- Current Role Badge -->
                @if(current_team_role())
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-600">Role:</span>
                        <x-role-badge :role="current_team_role()->key"/>
                    </div>
                @endif

                <!-- Teams Dropdown -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures() && auth()->user()->currentTeam)
                    <div class="relative">
                        <x-dropdown align="right" width="96">
                            <x-slot name="trigger">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150 shadow-sm">
                                    <svg class="size-5 text-gray-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span class="max-w-[150px] truncate">{{ auth()->user()->currentTeam->name }}</span>
                                    @teamowner
                                    <svg class="size-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    @endteamowner
                                    <svg class="size-4 text-gray-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="w-80">
                                    <!-- Current Team Info -->
                                    <div
                                        class="px-4 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->currentTeam->name }}</p>
                                                <p class="text-xs text-gray-600 mt-1">{{ auth()->user()->currentTeam->allUsers()->count() }}
                                                    members</p>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                @if(current_team_role())
                                                    <x-role-badge :role="current_team_role()->key"/>
                                                @endif
                                                @teamowner
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Owner
                                                    </span>
                                                @endteamowner
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Team Management -->
                                    <div class="py-1">
                                        <div
                                            class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                            {{ __('Manage Team') }}
                                        </div>

                                        <x-dropdown-link
                                            href="{{ route('teams.show', auth()->user()->currentTeam->id) }}"
                                            class="flex items-center gap-2">
                                            <svg class="size-4 text-gray-400" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ __('Team Settings') }}
                                        </x-dropdown-link>

                                        @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                            <x-dropdown-link href="{{ route('teams.create') }}"
                                                             class="flex items-center gap-2">
                                                <svg class="size-4 text-gray-400" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                {{ __('Create New Team') }}
                                            </x-dropdown-link>
                                        @endcan
                                    </div>

                                    <!-- Team Switcher -->
                                    @if (auth()->user()->allTeams()->count() > 1)
                                        <div class="border-t border-gray-200"></div>

                                        <div class="py-1">
                                            <div
                                                class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                                {{ __('Switch Teams') }}
                                            </div>

                                            <div class="max-h-60 overflow-y-auto">
                                                @foreach (auth()->user()->allTeams() as $team)
                                                    <form method="POST" action="{{ route('current-team.update') }}"
                                                          x-data>
                                                        @method('PUT')
                                                        @csrf
                                                        <input type="hidden" name="team_id" value="{{ $team->id }}">

                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors duration-150 {{ $team->id === auth()->user()->currentTeam->id ? 'bg-blue-50' : '' }}">
                                                            <div class="flex items-center gap-3">
                                                                <!-- Active Indicator -->
                                                                <div class="flex-shrink-0">
                                                                    @if ($team->id === auth()->user()->currentTeam->id)
                                                                        <div
                                                                            class="size-2 rounded-full bg-blue-600"></div>
                                                                    @else
                                                                        <div
                                                                            class="size-2 rounded-full bg-gray-300"></div>
                                                                    @endif
                                                                </div>

                                                                <!-- Team Info -->
                                                                <div class="flex-1 min-w-0">
                                                                    <p class="font-medium text-gray-900 truncate">{{ $team->name }}</p>
                                                                    <p class="text-xs text-gray-500">{{ $team->allUsers()->count() }}
                                                                        members</p>
                                                                </div>

                                                                <!-- Role Badge (Compact) -->
                                                                <div class="flex-shrink-0">
                                                                    @if(auth()->user()->ownsTeam($team))
                                                                        <span
                                                                            class="inline-flex items-center justify-center size-6 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold"
                                                                            title="Owner">
                                                                            <svg class="size-3.5" fill="currentColor"
                                                                                 viewBox="0 0 20 20">
                                                                                <path
                                                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                                            </svg>
                                                                        </span>
                                                                    @else
                                                                        @php
                                                                            $userRole = auth()->user()->teamRole($team);
                                                                        @endphp
                                                                        @if($userRole)
                                                                            <x-role-badge :role="$userRole->key"/>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endif

                <!-- Settings Dropdown -->
                <div class="relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <button
                                    class="flex items-center gap-2 text-sm border-2 border-gray-200 rounded-full p-0.5 hover:border-gray-300 focus:outline-none focus:border-blue-500 transition">
                                    <img class="size-8 rounded-full object-cover"
                                         src="{{ auth()->user()->profile_photo_url }}"
                                         alt="{{ auth()->user()->name }}"/>
                                </button>
                            @else
                                <button type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                    {{ auth()->user()->name }}
                                    <svg class="ms-2 -me-0.5 size-4" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                    </svg>
                                </button>
                            @endif
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Info -->
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center gap-3">
                                    <img class="size-10 rounded-full object-cover"
                                         src="{{ auth()->user()->profile_photo_url }}"
                                         alt="{{ auth()->user()->name }}"/>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Management -->
                            <div class="py-1">
                                <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {{ __('Account') }}
                                </div>

                                <x-dropdown-link href="{{ route('profile.show') }}" class="flex items-center gap-2">
                                    <svg class="size-4 text-gray-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                    <x-dropdown-link href="{{ route('api-tokens.index') }}"
                                                     class="flex items-center gap-2">
                                        <svg class="size-4 text-gray-400" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                        {{ __('API Tokens') }}
                                    </x-dropdown-link>
                                @endif
                            </div>

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf
                                    <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();"
                                                     class="flex items-center gap-2 text-red-600 hover:bg-red-50">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="size-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                              stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @teamcan('read')
            <x-responsive-nav-link href="#" :active="request()->routeIs('projects.index')">
                {{ __('Projects') }}
            </x-responsive-nav-link>
            @endteamcan

            @teamadmin
            <x-responsive-nav-link href="#" :active="request()->routeIs('reports.*')">
                {{ __('Reports') }}
            </x-responsive-nav-link>
            @endteamadmin
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <!-- User Info -->
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <div class="flex items-center px-4 mb-3">
                    <div class="shrink-0 me-3">
                        <img class="size-10 rounded-full object-cover" src="{{ auth()->user()->profile_photo_url }}"
                             alt="{{ auth()->user()->name }}"/>
                    </div>
                    <div>
                        <div class="font-medium text-base text-gray-800">{{ auth()->user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            @else
                <div class="px-4 mb-3">
                    <div class="font-medium text-base text-gray-800">{{ auth()->user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                </div>
            @endif

            <!-- Current Role -->
            @if(current_team_role())
                <div class="px-4 mb-3">
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-600">Current Role:</span>
                        <x-role-badge :role="current_team_role()->key"/>
                    </div>
                </div>
            @endif

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-responsive-nav-link href="{{ route('api-tokens.index') }}"
                                           :active="request()->routeIs('api-tokens.index')">
                        {{ __('API Tokens') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>

                <!-- Team Management -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures() && auth()->user()->currentTeam)
                    <div class="border-t border-gray-200"></div>

                    <div class="block px-4 py-2 text-xs text-gray-400">
                        {{ __('Manage Team') }}
                    </div>

                    <!-- Team Settings -->
                    <x-responsive-nav-link href="{{ route('teams.show', auth()->user()->currentTeam->id) }}"
                                           :active="request()->routeIs('teams.show')">
                        {{ __('Team Settings') }}
                    </x-responsive-nav-link>

                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                        <x-responsive-nav-link href="{{ route('teams.create') }}"
                                               :active="request()->routeIs('teams.create')">
                            {{ __('Create New Team') }}
                        </x-responsive-nav-link>
                    @endcan

                    <!-- Team Switcher -->
                    @if (auth()->user()->allTeams()->count() > 1)
                        <div class="border-t border-gray-200"></div>

                        <div class="block px-4 py-2 text-xs text-gray-400">
                            {{ __('Switch Teams') }}
                        </div>

                        @foreach (auth()->user()->allTeams() as $team)
                            <x-switchable-team :team="$team" component="responsive-nav-link"/>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</nav>
