{{--
    Toolbar search + "Filter & Sort" dropdown, dipakai di halaman index/list module.

    Props:
    - route       : nama route index (tanpa parameter), action form GET.
    - filters     : array filter yang sedang aktif (dari Controller), dipakai untuk
                    isi ulang input search dan hidden input status.
    - placeholder : placeholder input search.

    Slot diisi field filter/sort khusus module (select, dst) -- lihat pemakaian di
    resources/views/players/index.blade.php. Komponen ini cuma menyediakan mekanisme
    search + dropdown-nya, bukan field filter itu sendiri, supaya bisa dipakai ulang
    oleh module lain dengan field yang berbeda-beda.
--}}
@props(['route', 'filters' => [], 'placeholder' => null])

<div class="table-toolbar">
    <form action="{{ route($route) }}" method="GET" class="table-toolbar-form">

        {{-- Status dikendalikan oleh <x-table.tabs>, bukan form ini -- dipertahankan
             lewat hidden input supaya submit search/filter tidak mereset tab aktif. --}}
        @if (!empty($filters['status']))
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        <div class="table-toolbar-search">
            <svg class="table-toolbar-search-icon" width="18" height="18" viewBox="0 0 20 20" fill="none">
                <path
                    d="M9.16667 15.8333C12.8486 15.8333 15.8333 12.8486 15.8333 9.16667C15.8333 5.48477 12.8486 2.5 9.16667 2.5C5.48477 2.5 2.5 5.48477 2.5 9.16667C2.5 12.8486 5.48477 15.8333 9.16667 15.8333Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M17.5 17.5L13.875 13.875" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>

            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                placeholder="{{ $placeholder ?? __('Cari...') }}" class="form-input pl-10">
        </div>

        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="btn btn-secondary shrink-0">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                    <path
                        d="M2.5 5H17.5M5.83333 10H14.1667M8.33333 15H11.6667"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>{{ __('Filter & Sort') }}</span>
            </button>

            <div x-show="open" x-cloak @click.away="open = false" x-transition
                class="dropdown-menu right-0 w-72 space-y-4 p-4">

                {{ $slot }}

                <div class="flex items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                    <a href="{{ route($route, array_filter(['status' => $filters['status'] ?? null])) }}"
                        class="link-muted text-sm">
                        {{ __('Reset Filter') }}
                    </a>

                    <button type="submit" class="btn btn-primary btn-sm">
                        {{ __('Terapkan') }}
                    </button>
                </div>

            </div>
        </div>

    </form>
</div>
