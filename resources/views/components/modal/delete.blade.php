<div x-data="deleteModal" @delete-confirm.window="open($event.detail.action,$event.detail.name)" x-show="show"
    class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">
            <div class="flex items-center gap-4">
                <span class="modal-icon modal-icon-danger">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 9V14M12 17.01L12.01 16.9989M12 3L2 21H22L12 3Z" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>

                <div>
                    <h3 class="modal-title">
                        Konfirmasi Hapus
                    </h3>

                    <p class="modal-description">
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                Apakah Anda yakin ingin menghapus data
                <strong class="font-semibold text-gray-800 dark:text-white" x-text="name">
                </strong>?
                Semua data terkait akan dihapus secara permanen.
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close()">
                Batal
            </button>

            <form :action="action" method="POST">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Hapus Permanen
                </button>
            </form>
        </div>

    </div>
</div>
