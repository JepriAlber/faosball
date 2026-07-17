{{--
    Pagination view kustom FAOSBall -- dipasang sebagai default global lewat
    Paginator::defaultView() di AppServiceProvider::boot(), jadi otomatis
    dipakai oleh SELURUH halaman index yang memanggil {{ $x->links() }},
    tanpa perlu menyebutkan nama view ini satu-satu di tiap Blade.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-wrapper">

        <p class="pagination-info">
            Menampilkan <span class="font-medium text-gray-600 dark:text-gray-300">{{ $paginator->firstItem() }}</span>
            &ndash;
            <span class="font-medium text-gray-600 dark:text-gray-300">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-medium text-gray-600 dark:text-gray-300">{{ $paginator->total() }}</span>
            data
        </p>

        <div class="pagination-nav">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-link pagination-link-disabled" aria-disabled="true">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-link" rel="prev">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-link pagination-link-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-link">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif

            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-link" rel="next">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            @else
                <span class="pagination-link pagination-link-disabled" aria-disabled="true">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            @endif

        </div>

    </nav>
@endif
