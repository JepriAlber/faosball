<div class="breadcrumb-header">
    <h2 class="breadcrumb-title">
        {{ $title }}
    </h2>

    <nav>
        <ol class="breadcrumb-list">
            <li>
                <a href="{{ url('/') }}" class="breadcrumb-link">
                    Home
                    <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="currentColor" stroke-width="1.2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </li>

            @if (count($items))
                @foreach ($items as $item)
                    <li class="breadcrumb-item">
                        @if (isset($item['url']))
                            <a href="{{ $item['url'] }}" class="breadcrumb-item-link">
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span class="breadcrumb-item-current">
                                {{ $item['label'] }}
                            </span>
                        @endif

                        @if (!$loop->last)
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="currentColor"
                                    stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @endif
                    </li>
                @endforeach
            @else
                <li class="breadcrumb-item-current">
                    {{ $title }}
                </li>
            @endif
        </ol>
    </nav>
</div>
