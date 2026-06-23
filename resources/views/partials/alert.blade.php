@if (session('success'))
    <div
        class="mb-6 flex w-full items-center justify-between rounded-xl bg-green-50 p-4 border border-green-200 dark:bg-green-500/15 dark:border-green-500/30">
        <div class="flex items-center gap-3">
            <span
                class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-500">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M16.7071 5.29289C17.0976 5.68342 17.0976 6.31658 16.7071 6.70711L8.70711 14.7071C8.31658 15.0976 7.68342 15.0976 7.29289 14.7071L3.29289 10.7071C2.90237 10.3166 2.90237 9.68342 3.29289 9.29289C3.68342 8.90237 4.31658 8.90237 4.70711 9.29289L8 12.5858L15.2929 5.29289C15.6834 4.90237 16.3166 4.90237 16.7071 5.29289Z"
                        fill="currentColor" />
                </svg>
            </span>
            <p class="text-sm font-medium text-green-800 dark:text-green-400">
                {{ session('success') }}
            </p>
        </div>
    </div>
@endif

@if (session('error'))
    <div
        class="mb-6 flex w-full items-center justify-between rounded-xl bg-red-50 p-4 border border-red-200 dark:bg-red-500/15 dark:border-red-500/30">
        <div class="flex items-center gap-3">
            <span
                class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-500">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M4.29289 4.29289C4.68342 3.90237 5.31658 3.90237 5.70711 4.29289L10 8.58579L14.2929 4.29289C14.6834 3.90237 15.3166 3.90237 15.7071 4.29289C16.0976 4.68342 16.0976 5.31658 15.7071 5.70711L11.4142 10L15.7071 14.2929C16.0976 14.6834 16.0976 15.3166 15.7071 15.7071C15.3166 16.0976 14.6834 16.0976 14.2929 15.7071L10 11.4142L5.70711 15.7071C5.31658 16.0976 4.68342 16.0976 4.29289 15.7071C3.90237 15.3166 3.90237 14.6834 4.29289 14.2929L8.58579 10L4.29289 5.70711C3.90237 5.31658 3.90237 4.68342 4.29289 4.29289Z"
                        fill="currentColor" />
                </svg>
            </span>
            <p class="text-sm font-medium text-red-800 dark:text-red-400">
                {{ session('error') }}
            </p>
        </div>
    </div>
@endif
