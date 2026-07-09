@props(['action', 'name', 'disabled' => false, 'reason' => null])

@if ($disabled)
    <button type="button" class="btn-icon btn-icon-danger cursor-not-allowed opacity-40"
        title="{{ $reason ?? 'Tidak dapat dihapus' }}" disabled>

        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path
                d="M3.75 5H16.25M7.5 5V3.75C7.5 3.41848 7.6317 3.10054 7.86612 2.86612C8.10054 2.6317 8.41848 2.5 8.75 2.5H11.25C11.5815 2.5 11.8995 2.6317 12.1339 2.86612C12.3683 3.10054 12.5 3.41848 12.5 3.75V5M14.375 5V16.25C14.375 16.5815 14.2433 16.8995 14.0089 17.1339C13.7745 17.3683 13.4565 17.5 13.125 17.5H6.875C6.54348 17.5 6.22554 17.3685 5.99112 17.1339C5.7567 16.8995 5.625 16.5815 5.625 16.25V5H14.375Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
@else
    <button type="button" @click="$dispatch('delete-confirm',{action:'{{ $action }}',name:'{{ addslashes($name) }}'})"
        class="btn-icon btn-icon-danger" title="Hapus">

        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path
                d="M3.75 5H16.25M7.5 5V3.75C7.5 3.41848 7.6317 3.10054 7.86612 2.86612C8.10054 2.6317 8.41848 2.5 8.75 2.5H11.25C11.5815 2.5 11.8995 2.6317 12.1339 2.86612C12.3683 3.10054 12.5 3.41848 12.5 3.75V5M14.375 5V16.25C14.375 16.5815 14.2433 16.8995 14.0089 17.1339C13.7745 17.3683 13.4565 17.5 13.125 17.5H6.875C6.54348 17.5 6.22554 17.3685 5.99112 17.1339C5.7567 16.8995 5.625 16.5815 5.625 16.25V5H14.375Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
@endif
