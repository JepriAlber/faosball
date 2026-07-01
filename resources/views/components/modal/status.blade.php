<div x-data="statusModal" @status-confirm.window="open($event.detail.action,$event.detail.name,$event.detail.status)"
    x-show="show" class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">

            <div class="flex items-center gap-4">

                <span class="modal-icon" :class="status ? 'modal-icon-danger' : 'modal-icon-success'">

                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">

                        <path d="M12 8V12M12 16H12.01M12 3L2 21H22L12 3Z" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />

                    </svg>

                </span>


                <div>

                    <h3 class="modal-title">

                        <span x-text="status ? 'Disable Account' : 'Enable Account'"></span>

                    </h3>


                    <p class="modal-description">

                        <span
                            x-text="status
                            ? 'Account akan dinonaktifkan'
                            : 'Account akan diaktifkan kembali'">
                        </span>

                    </p>

                </div>

            </div>

        </div>


        <div class="modal-body">

            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">

                Apakah Anda yakin ingin

                <strong class="text-gray-800 dark:text-white">

                    <span x-text="status ? 'menonaktifkan' : 'mengaktifkan'"></span>

                </strong>

                account

                <strong class="text-gray-800 dark:text-white" x-text="name"></strong>

                ?

            </p>

        </div>


        <div class="modal-footer">

            <button type="button" class="btn btn-secondary" @click="close()">
                Batal
            </button>


            <form :action="action" method="POST">

                @csrf
                @method('PATCH')

                <button type="submit" class="btn" :class="status ? 'btn-danger' : 'btn-primary'">
                    <span x-text="status ? 'Disable' : 'Enable'"></span>
                </button>

            </form>

        </div>

    </div>

</div>
