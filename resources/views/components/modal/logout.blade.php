<div x-data="logoutModal" @logout-confirm.window="open()" x-show="show"
    class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">
            <div class="flex items-center gap-4">
                <span class="modal-icon modal-icon-danger">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 8V12M12 16H12.01M12 3L2 21H22L12 3Z" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>

                <div>
                    <h3 class="modal-title">
                        Keluar dari Sistem
                    </h3>
                    <p class="modal-description">
                        Sesi Anda akan diakhiri.
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                Apakah Anda yakin ingin keluar dari sistem?
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close()">
                Batal
            </button>

            <form action="{{ route('logout') }}" method="POST">
                @csrf

                <button type="submit" class="btn btn-danger">
                    Sign Out
                </button>
            </form>
        </div>

    </div>
</div>
