<div x-data="leaveTeamModal" @leave-team-confirm.window="open($event.detail.action,$event.detail.name)"
    x-show="show" class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">
            <div class="flex items-center gap-4">
                <span class="modal-icon modal-icon-danger">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </span>

                <div>
                    <h3 class="modal-title">
                        {{ __('Keluarkan dari Tim') }}
                    </h3>

                    <p class="modal-description">
                        {{ __('Riwayat keanggotaan tetap tersimpan.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                {{ __('Apakah Anda yakin ingin mengeluarkan') }}
                <strong class="font-semibold text-gray-800 dark:text-white" x-text="name"></strong>
                {{ __('dari tim ini?') }}
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close()">
                {{ __('Batal') }}
            </button>

            <form :action="action" method="POST">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-danger">
                    {{ __('Keluarkan') }}
                </button>
            </form>
        </div>

    </div>
</div>
