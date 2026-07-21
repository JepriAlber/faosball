@if ($url)
    <img {{ $attributes->merge(['src' => $url, 'alt' => 'Logo']) }} />
@elseif ($isFavicon)
    <span
        {{ $attributes->merge(['class' => 'flex size-8 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-xs font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400']) }}>{{ $fallbackText }}</span>
@else
    <span
        {{ $attributes->merge(['class' => 'block max-w-[160px] truncate text-lg font-bold text-brand-600 dark:text-brand-400']) }}>{{ $fallbackText }}</span>
@endif
