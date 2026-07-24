@props(['action', 'name'])

<button type="button"
    @click="$dispatch('leave-team-confirm',{action:'{{ $action }}',name:'{{ addslashes($name) }}'})"
    class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">

    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path
            d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</button>
