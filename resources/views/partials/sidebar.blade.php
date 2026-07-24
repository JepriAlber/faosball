<aside :class="sidebarToggle ? 'sidebar-collapsed' : 'sidebar-expanded'" class="sidebar">
    <!-- SIDEBAR HEADER -->
    <div class="sidebar-header justify-center">
        <a href="{{ route('dashboard') }}">
            {{--
        Logo teks: HANYA tampil di desktop (lg ke atas).
        Di mobile sidebar tidak menampilkan logo sama sekali
        karena header sudah memiliki logo sendiri.
      --}}
            <span class="logo hidden lg:block" :class="sidebarToggle ? 'lg:hidden' : ''">
                <x-academy-logo variant="sidebar" class="dark:hidden" />
                <x-academy-logo variant="sidebar" class="hidden dark:block" />
            </span>
            

            {{-- Logo ikon kecil: hanya tampil di desktop saat sidebar collapsed --}}
            <x-academy-logo variant="favicon" class="logo-icon" ::class="sidebarToggle ? 'lg:block' : 'hidden'" />
        </a>
    </div>
    <!-- SIDEBAR HEADER -->

    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">

        <!-- Sidebar Menu -->
        <nav x-data="{ selected: $persist('').as('sidebar-open') }">

            <!-- ===================== MENU GROUP ===================== -->
            <div class="mb-6">
                <h3 class="menu-group-heading">
                    <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                        {{ __('MENU') }}
                    </span>
                    {{-- Dots icon saat sidebar collapsed di desktop --}}
                    <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'" class="menu-group-icon" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                            fill="" />
                    </svg>
                </h3>

                <ul class="flex flex-col gap-4">

                    <!-- ===== Menu Item: Dashboard (dengan dropdown) ===== -->
                    @php
                        $dashboardRoutes = ['dashboard']; //tambah nama route lain jika ada sub-halaman
                        $isDashboardActive = in_array(Route::currentRouteName(), $dashboardRoutes);
                    @endphp

                    <li x-data="{ open: {{ $isDashboardActive ? 'true' : 'false' }}, active: {{ $isDashboardActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="active ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg :class="active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3.25" y="3.25" width="8" height="8" rx="1.5" fill="none" stroke=""
                                    stroke-width="1.8" />
                                <rect x="12.75" y="3.25" width="8" height="8" rx="1.5" fill="none" stroke=""
                                    stroke-width="1.8" />
                                <rect x="3.25" y="12.75" width="8" height="8" rx="1.5" fill="none" stroke=""
                                    stroke-width="1.8" />
                                <rect x="12.75" y="12.75" width="8" height="8" rx="1.5" fill="none" stroke=""
                                    stroke-width="1.8" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Dashboard') }}
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[
                                    active ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                    open ? 'rotate-180' : '',
                                    sidebarToggle ? 'lg:hidden' : ''
                                ]"
                                width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>

                        {{-- Dropdown sub-menu --}}
                        <div x-show="open" x-collapse class="overflow-hidden">
                            <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">
                                <li>
                                    <a href="{{ route('dashboard') }}" class="menu-dropdown-item group"
                                        :class="{{ Route::is('dashboard') ? 'true' : 'false' }} ? 'menu-dropdown-item-active' :
                                            'menu-dropdown-item-inactive'">
                                        {{ __('Ringkasan') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <!-- ===== END: Dashboard ===== -->


                    <h3 class="menu-group-heading">
                        <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                            {{ __('football academy') }}
                        </span>
                        {{-- Dots icon saat sidebar collapsed di desktop --}}
                        <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'" class="menu-group-icon"
                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                                fill="" />
                        </svg>
                    </h3>

                    {{--
                        Heading "Football Academy" menaungi DUA dropdown terpisah
                        (Players & Teams) sebagai saudara, pola sama persis
                        "Administrasi" yang menaungi dropdown "Roles & Permissions"
                        + "Master" -- bukan dropdown bersarang, cuma 2 <li> dropdown
                        di bawah 1 heading yang sama.
                    --}}

                    {{-- ===== Menu Item: Players (dengan dropdown) ===== --}}

                    @php
                        $playersRoutes = ['players.*', 'player-types.*', 'player-categories.*'];

                        $isPlayersActive = false;

                        foreach ($playersRoutes as $route) {
                            if (Route::is($route)) {
                                $isPlayersActive = true;
                                break;
                            }
                        }

                    @endphp

                    <li x-data="{ open: {{ $isPlayersActive ? 'true' : 'false' }}, active: {{ $isPlayersActive ? 'true' : 'false' }} }">

                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="active ? 'menu-item-active' : 'menu-item-inactive'">

                            <svg :class="active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M5 20v-1a7 7 0 0 1 14 0v1" fill="none" stroke="" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Players') }}
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[
                                    active ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                    open ? 'rotate-180' : '',
                                    sidebarToggle ? 'lg:hidden' : ''
                                ]"
                                width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                        </a>

                        {{-- Dropdown submenu --}}

                        <div x-show="open" x-collapse class="overflow-hidden">

                            <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">
                                {{-- Players --}}
                                @can('player.view')
                                    <li>
                                        <a href="{{ route('players.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('players.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Players') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Player Types --}}
                                @can('player_type.view')
                                    <li>
                                        <a href="{{ route('player-types.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('player-types.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Player Types') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Player Categories --}}
                                @can('player_category.view')
                                    <li>
                                        <a href="{{ route('player-categories.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('player-categories.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Player Categories') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>

                    <!-- ===== END: Players ===== -->

                    {{-- ===== Menu Item: Teams (dengan dropdown) ===== --}}

                    @php
                        $teamsRoutes = ['teams.*', 'seasons.*', 'team-staff-positions.*', 'training.*'];

                        $isTeamsActive = false;

                        foreach ($teamsRoutes as $route) {
                            if (Route::is($route)) {
                                $isTeamsActive = true;
                                break;
                            }
                        }

                    @endphp

                    <li x-data="{ open: {{ $isTeamsActive ? 'true' : 'false' }}, active: {{ $isTeamsActive ? 'true' : 'false' }} }">

                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="active ? 'menu-item-active' : 'menu-item-inactive'">

                            <svg :class="active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="9" cy="7" r="4" fill="none" stroke="" stroke-width="1.8" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Teams') }}
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[
                                    active ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                    open ? 'rotate-180' : '',
                                    sidebarToggle ? 'lg:hidden' : ''
                                ]"
                                width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                        </a>

                        {{-- Dropdown submenu --}}

                        <div x-show="open" x-collapse class="overflow-hidden">

                            <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">
                                {{-- Teams --}}
                                @can('team.view')
                                    <li>
                                        <a href="{{ route('teams.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('teams.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Teams') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Seasons --}}
                                @can('season.view')
                                    <li>
                                        <a href="{{ route('seasons.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('seasons.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Seasons') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Team Staff Positions --}}
                                @can('team_staff_position.view')
                                    <li>
                                        <a href="{{ route('team-staff-positions.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('team-staff-positions.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Team Staff Positions') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Training nanti --}}
                                {{--
                                    <li>
                                        <a href="{{ route('training.index') }}"
                                            class="menu-dropdown-item group"
                                            :class="{{ Route::is('training.*') ? 'true' : 'false' }}
                                            ? 'menu-dropdown-item-active'
                                            : 'menu-dropdown-item-inactive'">
                                            Training
                                        </a>
                                    </li>
                                    --}}
                            </ul>
                        </div>
                    </li>

                    <!-- ===== END: Teams ===== -->

                    <h3 class="menu-group-heading">
                        <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                            {{ __('office') }}
                        </span>
                        {{-- Dots icon saat sidebar collapsed di desktop --}}
                        <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'" class="menu-group-icon"
                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                                fill="" />
                        </svg>
                    </h3>

                    <!-- ===== Menu Item: Office (dengan dropdown) ===== -->

                    @php
                        $officeRoutes = ['staff.*', 'employment-contracts.*', 'staff-positions.*', 'employment-types.*'];

                        $isOfficeActive = false;

                        foreach ($officeRoutes as $route) {
                            if (Route::is($route)) {
                                $isOfficeActive = true;
                                break;
                            }
                        }
                    @endphp

                    <li x-data="{ open: {{ $isOfficeActive ? 'true' : 'false' }}, active: {{ $isOfficeActive ? 'true' : 'false' }} }">

                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="active ? 'menu-item-active' : 'menu-item-inactive'">

                            <svg :class="active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 21V5a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v16" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M3 21h18" fill="none" stroke="" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M9 8h1M14 8h1M9 12h1M14 12h1M9 16h1M14 16h1" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Office') }}
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[
                                    active ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                    open ? 'rotate-180' : '',
                                    sidebarToggle ? 'lg:hidden' : ''
                                ]"
                                width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                        </a>

                        {{-- Dropdown submenu --}}

                        <div x-show="open" x-collapse class="overflow-hidden">

                            <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">

                                {{-- Staff --}}
                                @can('staff.view')
                                    <li>
                                        <a href="{{ route('staff.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('staff.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Staff') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Kontrak Kerja --}}
                                @can('staff.view')
                                    <li>
                                        <a href="{{ route('employment-contracts.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('employment-contracts.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Kontrak Kerja') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Staff Position --}}
                                @can('staff_position.view')
                                    <li>
                                        <a href="{{ route('staff-positions.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('staff-positions.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Staff Position') }}
                                        </a>
                                    </li>
                                @endcan

                                {{-- Employment Type --}}
                                @can('employment_type.view')
                                    <li>
                                        <a href="{{ route('employment-types.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('employment-types.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Employment Type') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>

                    <!-- ===== END: Office ===== -->

                    <!-- ===== Menu Item: Profile (tanpa dropdown) ===== -->
                    @php
                        $isProfileActive = Route::is('profile');
                    @endphp

                    <li>
                        <a href="#"
                            class="menu-item group {{ $isProfileActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ $isProfileActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 20v-1a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v1" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" fill="none" stroke=""
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Profile') }}
                            </span>
                        </a>
                    </li>
                    <!-- ===== END: Profile ===== -->


                    {{-- ===== Menu Item: Profil Academy (tanpa dropdown) ===== --}}
                    {{--
                        Digabung dengan !isSuperAdmin() karena Gate::before() (lihat
                        AppServiceProvider) meloloskan Super Admin dari SELURUH
                        permission check, termasuk @can di sini -- padahal modul ini
                        tidak relevan untuk Super Admin (sudah ada modul Academy
                        Management untuk kelola academy lintas tenant).
                    --}}
                    @can('academy_profile.update')
                        @if (! app(\App\Services\AcademyService::class)->isSuperAdmin())
                        @php
                            $isAcademyProfileActive = Route::is('academy.profile.*');
                        @endphp

                        <li>
                            <a href="{{ route('academy.profile.edit') }}"
                                class="menu-item group {{ $isAcademyProfileActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                                <svg class="{{ $isAcademyProfileActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 21V8L12 3L20 8V21H14V14H10V21H4Z" fill="none" stroke=""
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    {{ __('Profil Academy') }}
                                </span>
                            </a>
                        </li>
                        @endif
                    @endcan
                    <!-- ===== END: Profil Academy ===== -->


                    {{-- ===== Administrasi ===== --}}
                    {{--
                        Beda dengan section lain: heading ini SENGAJA tidak digerbang
                        isSuperAdmin() murni, karena Owner juga berhak melihat Roles
                        (role.* ada di role_templates Owner, dibatasi ke academy-nya
                        sendiri lewat Role::scopeForCurrentAcademy()) -- Academy,
                        Permissions, dan Master TETAP Super-Admin-only karena
                        academy.*/permission.*/player_position.* sengaja tidak pernah
                        ada di role_templates manapun (lihat docs/permission-reference.md).
                    --}}
                    @if (app(\App\Services\AcademyService::class)->isSuperAdmin() || auth()->user()->can('role.view'))
                        <h3 class="menu-group-heading">
                            <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Administrasi') }}
                            </span>
                            {{-- Dots icon saat sidebar collapsed di desktop --}}
                            <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'" class="menu-group-icon"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                                    fill="" />
                            </svg>
                        </h3>

                        {{-- ===== Menu Item: Academy (tanpa dropdown, Super Admin only) ===== --}}
                        @can('academy.view')
                            <li>
                                <a href="{{ route('academies.index') }}"
                                    class="menu-item group {{ Route::is('academies.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                                    <svg class="{{ Route::is('academies.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="8.5" fill="none" stroke="" stroke-width="1.8" />
                                        <path d="M12 8L14.2 9.6L13.4 12.2H10.6L9.8 9.6L12 8Z" fill="none" stroke=""
                                            stroke-width="1.3" stroke-linejoin="round" />
                                        <path
                                            d="M12 8V5.2M14.2 9.6L16.6 8.2M13.4 12.2L15.1 14.5M10.6 12.2L8.9 14.5M9.8 9.6L7.4 8.2"
                                            fill="none" stroke="" stroke-width="1.2" stroke-linecap="round" />
                                    </svg>

                                    <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                        {{ __('Academy') }}
                                    </span>
                                </a>
                            </li>
                        @endcan
                        {{-- ===== END: Academy ===== --}}

                        {{-- ===== Menu Item: Roles & Permissions (dengan dropdown) ===== --}}
                        @php
                            $isRolesPermissionsActive = Route::is('roles.*') || Route::is('permissions.*');
                        @endphp

                        <li x-data="{ open: {{ $isRolesPermissionsActive ? 'true' : 'false' }}, active: {{ $isRolesPermissionsActive ? 'true' : 'false' }} }">

                            <a href="#" @click.prevent="open=!open" class="menu-item group"
                                :class="active ? 'menu-item-active' : 'menu-item-inactive'">
                                <svg :class="active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="" stroke-width="1.8"
                                        stroke-linecap="round" />

                                </svg>
                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    {{ __('Roles & Permissions') }}
                                </span>
                                <svg class="menu-item-arrow transition-transform duration-200"
                                    :class="[
                                        active ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                        open ? 'rotate-180' : '',
                                        sidebarToggle ? 'lg:hidden' : ''
                                    ]"
                                    width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>

                            <div x-show="open" x-collapse class="overflow-hidden">

                                <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">
                                    {{-- Roles: Super Admin (semua academy) + Owner (academy sendiri) --}}
                                    @can('role.view')
                                        <li>
                                            <a href="{{ route('roles.index') }}" class="menu-dropdown-item group"
                                                :class="{{ Route::is('roles.*') ? 'true' : 'false' }}
                                                    ?
                                                    'menu-dropdown-item-active' :
                                                    'menu-dropdown-item-inactive'">
                                                {{ __('Roles') }}

                                            </a>
                                        </li>
                                    @endcan

                                    {{-- Permissions: Super Admin only --}}
                                    @can('permission.view')
                                        <li>
                                            <a href="{{ route('permissions.index') }}" class="menu-dropdown-item group"
                                                :class="{{ Route::is('permissions.*') ? 'true' : 'false' }}
                                                    ?
                                                    'menu-dropdown-item-active' :
                                                    'menu-dropdown-item-inactive'">
                                                {{ __('Permissions') }}
                                            </a>
                                        </li>
                                    @endcan
                                </ul>

                            </div>

                        </li>
                        {{-- ===== END: Roles & Permissions ===== --}}

                        {{-- ===== Menu Item: Master (dengan dropdown) ===== --}}
                        {{--
                            Digerbang di level dropdown (bukan cuma item di dalamnya) --
                            kalau tidak, Owner (yang lolos gate "Administrasi" lewat
                            role.view tapi tidak punya player_position.view) akan tetap
                            melihat dropdown "Master" yang bisa diklik tapi kosong
                            isinya. Kalau nanti Master menampung item lain selain
                            Posisi Pemain, ganti jadi @canany([...daftar permission...]).
                        --}}
                        @can('player_position.view')
                        @php
                            $masterRoutes = ['player-positions.*'];

                            $isMasterActive = false;

                            foreach ($masterRoutes as $route) {
                                if (Route::is($route)) {
                                    $isMasterActive = true;
                                    break;
                                }
                            }
                        @endphp

                        <li x-data="{ open: {{ $isMasterActive ? 'true' : 'false' }}, active: {{ $isMasterActive ? 'true' : 'false' }} }">

                            <a href="#" @click.prevent="open=!open" class="menu-item group"
                                :class="active ? 'menu-item-active' : 'menu-item-inactive'">

                                <svg :class="active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <rect x="3.25" y="3.25" width="7.5" height="7.5" rx="1" fill="none" stroke=""
                                        stroke-width="1.8" />
                                    <rect x="13.25" y="3.25" width="7.5" height="7.5" rx="1" fill="none" stroke=""
                                        stroke-width="1.8" />
                                    <rect x="3.25" y="13.25" width="7.5" height="7.5" rx="1" fill="none" stroke=""
                                        stroke-width="1.8" />
                                    <rect x="13.25" y="13.25" width="7.5" height="7.5" rx="1" fill="none" stroke=""
                                        stroke-width="1.8" />
                                </svg>

                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    {{ __('Master') }}
                                </span>

                                <svg class="menu-item-arrow transition-transform duration-200"
                                    :class="[
                                        active ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                        open ? 'rotate-180' : '',
                                        sidebarToggle ? 'lg:hidden' : ''
                                    ]"
                                    width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                            </a>

                            <div x-show="open" x-collapse class="overflow-hidden">

                                <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">

                                    {{-- Posisi Pemain --}}
                                    @can('player_position.view')
                                        <li>
                                            <a href="{{ route('player-positions.index') }}"
                                                class="menu-dropdown-item group"
                                                :class="{{ Route::is('player-positions.*') ? 'true' : 'false' }}
                                                    ?
                                                    'menu-dropdown-item-active' :
                                                    'menu-dropdown-item-inactive'">
                                                {{ __('Posisi Pemain') }}
                                            </a>
                                        </li>
                                    @endcan

                                </ul>

                            </div>

                        </li>
                        @endcan
                        {{-- ===== END: Master ===== --}}
                    @endif
                    {{-- ===== END: Administrasi ===== --}}


                </ul>
            </div>
            <!-- END MENU GROUP -->

        </nav>
        <!-- Sidebar Menu -->

        <!-- Sign Out -->
        <div :class="sidebarToggle ? 'lg:hidden' : ''" class="sidebar-footer">
            <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Keluar dari akun?') }}
            </p>
            <button type="button" @click="$dispatch('logout-confirm')" class="sidebar-signout-btn">
                {{ __('Sign Out') }}
            </button>
        </div>

    </div>
</aside>
