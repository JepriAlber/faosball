<div x-data="{
    open: true,
    checkAll: false,
    toggleAll() {
        this.$refs.items.querySelectorAll('input[name=\'permissions[]\']').forEach(el => {
            el.checked = this.checkAll;
            el.dispatchEvent(new Event('change'));
        });
    },
    sync() {
        const items = Array.from(this.$refs.items.querySelectorAll('input[name=\'permissions[]\']'));
        this.checkAll = items.length && items.every(el => el.checked);
    },
    init() {
        this.$nextTick(() => this.sync());
    }
}" x-init="init()"
    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">

    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">

        <button type="button" @click="open=!open" class="flex flex-1 items-center gap-3 text-left">

            <svg class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />

            </svg>

            <div>

                <h4 class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ \Illuminate\Support\Str::headline($module) }}
                </h4>

                <p class="mt-1 text-xs text-gray-500">
                    {{ $items->count() }} {{ __('Permission') }}
                </p>

            </div>

        </button>

        <label class="flex items-center gap-2">

            <input type="checkbox" x-model="checkAll" @change="toggleAll()"
                class="form-checkbox rounded border-gray-300 text-brand-500 focus:ring-brand-500">

            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                {{ __('Pilih Semua') }}
            </span>

        </label>

    </div>

    <div x-show="open" x-ref="items" class="grid gap-3 p-5">

        @foreach ($items as $permission)
            <label
                class="group flex cursor-pointer items-start justify-between rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:border-brand-500 dark:hover:bg-gray-800">

                <div class="flex gap-3">

                    <span class="badge {{ $permission['badge'] }}">
                        {{ $permission['action'] }}
                    </span>

                    <div>

                        <p class="text-sm font-semibold text-gray-800 dark:text-white">
                            {{ $permission['label'] }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            {{ $permission['description'] }}
                        </p>

                    </div>

                </div>

                <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}"
                    @checked(in_array($permission['name'], $selected)) @change="sync()"
                    class="form-checkbox h-5 w-5 rounded border-gray-300 text-brand-500 focus:ring-brand-500">

            </label>
        @endforeach

    </div>

</div>
