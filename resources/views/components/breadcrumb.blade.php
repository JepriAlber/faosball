<div class="mb-6 flex flex-wrap items-center justify-between gap-3">

    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
        {{ $title }}
    </h2>

    <nav>
        <ol class="flex items-center gap-1.5">

            <li>
                <a href="{{ url('/') }}"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">

                    Home

                    <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="currentColor" stroke-width="1.2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>

                </a>
            </li>

            @if (count($items))

                @foreach ($items as $item)
                    <li class="flex items-center gap-1.5">

                        @if (isset($item['url']))
                            <a href="{{ $item['url'] }}"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">

                                {{ $item['label'] }}

                            </a>
                        @else
                            <span class="text-sm text-gray-800 dark:text-white/90">
                                {{ $item['label'] }}
                            </span>
                        @endif


                        @if (!$loop->last)
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16"
                                fill="none">

                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="currentColor"
                                    stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />

                            </svg>
                        @endif

                    </li>
                @endforeach
            @else
                <li class="text-sm text-gray-800 dark:text-white/90">
                    {{ $title }}
                </li>

            @endif

        </ol>
    </nav>

</div>
