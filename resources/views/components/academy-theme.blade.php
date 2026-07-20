@if ($ramp)
    <style>
        :root {
            --color-brand-25: {{ $ramp['25'] }};
            --color-brand-50: {{ $ramp['50'] }};
            --color-brand-100: {{ $ramp['100'] }};
            --color-brand-200: {{ $ramp['200'] }};
            --color-brand-300: {{ $ramp['300'] }};
            --color-brand-400: {{ $ramp['400'] }};
            --color-brand-500: {{ $ramp['500'] }};
            --color-brand-600: {{ $ramp['600'] }};
            --color-brand-700: {{ $ramp['700'] }};
            --color-brand-800: {{ $ramp['800'] }};
            --color-brand-900: {{ $ramp['900'] }};
            --color-brand-950: {{ $ramp['950'] }};
        }
    </style>
@endif
