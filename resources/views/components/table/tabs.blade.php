{{--
    Tabs status + count, dipakai di halaman index/list module.

    Props:
    - tabs   : array asosiatif [key => ['label' => string, 'count' => int]].
               key '' merepresentasikan "Semua" (tidak mengirim filter status).
    - active : key tab yang sedang aktif (kosong string kalau "Semua").
    - route  : nama route index (tanpa parameter), dipakai untuk membangun href tiap tab.

    Filter lain di query string (search/sort/dst) dipertahankan saat pindah tab,
    tapi "page" sengaja dibuang -- hasil filter beda, jadi paginasi mulai dari 1 lagi.
--}}
@props(['tabs', 'active' => '', 'route'])

<div class="tabs scrollbar-brand">
    @foreach ($tabs as $key => $tab)
        <a href="{{ route($route, array_filter(array_merge(request()->except(['status', 'page']), $key !== '' ? ['status' => $key] : []))) }}"
            class="tab {{ (string) $active === (string) $key ? 'tab-active' : '' }}">
            {{ $tab['label'] }}
            <span class="badge badge-sm {{ (string) $active === (string) $key ? 'badge-primary' : 'badge-secondary' }} ml-2">
                {{ $tab['count'] }}
            </span>
        </a>
    @endforeach
</div>
