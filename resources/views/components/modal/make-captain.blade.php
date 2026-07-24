<div x-data="makeCaptainModal"
    @make-captain-confirm.window="open($event.detail.action,$event.detail.name,$event.detail.jerseyNumber)"
    x-show="show" class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">
            <div class="flex items-center gap-4">
                <span class="modal-icon modal-icon-primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </span>

                <div>
                    <h3 class="modal-title">
                        {{ __('Jadikan Kapten') }}
                    </h3>

                    <p class="modal-description">
                        {{ __('Status kapten akan berpindah dari kapten sebelumnya.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                {{ __('Jadikan') }}
                <strong class="font-semibold text-gray-800 dark:text-white" x-text="name"></strong>
                {{ __('sebagai kapten tim ini?') }}
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close()">
                {{ __('Batal') }}
            </button>

            <form :action="action" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_captain" value="1">
                <input type="hidden" name="jersey_number" :value="jerseyNumber">

                <button type="submit" class="btn btn-primary">
                    {{ __('Jadikan Kapten') }}
                </button>
            </form>
        </div>

    </div>
</div>
