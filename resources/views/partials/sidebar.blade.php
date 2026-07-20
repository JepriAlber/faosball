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
                        MENU
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

                    <li x-data="{ open: {{ $isDashboardActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="open ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg :class="open ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z"
                                    fill="" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                Dashboard
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[open ? 'menu-item-arrow-active rotate-180' : 'menu-item-arrow-inactive',
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
                                        Ringkasan
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <!-- ===== END: Dashboard ===== -->


                    <h3 class="menu-group-heading">
                        <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                            football academy
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

                    <!-- ===== Menu Item: Football Academy (dengan dropdown) ===== -->

                    @php
                        $footballAcademyRoutes = ['players.*', 'player-types.*', 'player-categories.*', 'training.*'];

                        $isFootballAcademyActive = false;

                        foreach ($footballAcademyRoutes as $route) {
                            if (Route::is($route)) {
                                $isFootballAcademyActive = true;
                                break;
                            }
                        }

                    @endphp

                    <li x-data="{ open: {{ $isFootballAcademyActive ? 'true' : 'false' }} }">

                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="open ? 'menu-item-active' : 'menu-item-inactive'">

                            <svg :class="open ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M9,4 L4,6 L4,10 L8,8 L7,20 L17,20 L16,8 L20,10 L20,6 L15,4 Q12,7 9,4 Z"
                                    fill="" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                Football Academy
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[
                                    open ? 'menu-item-arrow-active rotate-180' : 'menu-item-arrow-inactive',
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
                                            Players
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
                                            Player Types
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
                                            Player Categories
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

                    <!-- ===== END: Football Academy ===== -->


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
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z"
                                    fill="" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                Profile
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
                                    <path d="M4 21V8L12 3L20 8V21H14V14H10V21H4Z" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    Profil Academy
                                </span>
                            </a>
                        </li>
                        @endif
                    @endcan
                    <!-- ===== END: Profil Academy ===== -->


                    {{-- ===== Administration ===== --}}
                    @if (app(\App\Services\AcademyService::class)->isSuperAdmin())
                        <h3 class="menu-group-heading">
                            <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                                Administration
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
                        @php
                            $administrationRoutes = ['roles.*', 'permissions.*', 'academies.*'];

                            $isAdministrationActive = false;

                            foreach ($administrationRoutes as $route) {
                                if (Route::is($route)) {
                                    $isAdministrationActive = true;
                                    break;
                                }
                            }
                        @endphp

                        <li x-data="{ open: {{ $isAdministrationActive ? 'true' : 'false' }} }">

                            <a href="#" @click.prevent="open=!open" class="menu-item group"
                                :class="open ? 'menu-item-active' : 'menu-item-inactive'">
                                <svg :class="open ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 5H20V7H4V5ZM4 11H20V13H4V11ZM4 17H20V19H4V17Z" fill="currentColor" />

                                </svg>
                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    Administration
                                </span>
                                <svg class="menu-item-arrow transition-transform duration-200"
                                    :class="[
                                        open ?
                                        'menu-item-arrow-active rotate-180' :
                                        'menu-item-arrow-inactive',
                                        sidebarToggle ? 'lg:hidden' : ''
                                    ]"
                                    width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>

                            <div x-show="open" x-collapse class="overflow-hidden">

                                <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">
                                    {{-- Roles --}}
                                    <li>
                                        <a href="{{ route('roles.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('roles.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            Roles

                                        </a>
                                    </li>

                                    {{-- Permissions --}}
                                    <li>
                                        <a href="{{ route('permissions.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('permissions.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            Permissions
                                        </a>
                                    </li>

                                    {{-- Academy --}}
                                    <li>
                                        <a href="{{ route('academies.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('academies.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            Academy
                                        </a>
                                    </li>

                                </ul>

                            </div>

                        </li>

                        {{-- ===== Menu Item: Master (dengan dropdown) ===== --}}
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

                        <li x-data="{ open: {{ $isMasterActive ? 'true' : 'false' }} }">

                            <a href="#" @click.prevent="open=!open" class="menu-item group"
                                :class="open ? 'menu-item-active' : 'menu-item-inactive'">

                                <svg :class="open ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M4 4H10V10H4V4ZM14 4H20V10H14V4ZM4 14H10V20H4V14ZM14 14H20V20H14V14Z"
                                        fill="currentColor" />
                                </svg>

                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    Master
                                </span>

                                <svg class="menu-item-arrow transition-transform duration-200"
                                    :class="[
                                        open ?
                                        'menu-item-arrow-active rotate-180' :
                                        'menu-item-arrow-inactive',
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
                                                Posisi Pemain
                                            </a>
                                        </li>
                                    @endcan

                                </ul>

                            </div>

                        </li>
                        {{-- ===== END: Master ===== --}}
                    @endif
                    {{-- ===== END: Administration ===== --}}


                </ul>
            </div>
            <!-- END MENU GROUP -->

        </nav>
        <!-- Sidebar Menu -->

        <!-- Sign Out -->
        <div :class="sidebarToggle ? 'lg:hidden' : ''" class="sidebar-footer">
            <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                Keluar dari akun?
            </p>
            <button type="button" @click="$dispatch('logout-confirm')" class="sidebar-signout-btn">
                Sign Out
            </button>
        </div>

    </div>
</aside>
