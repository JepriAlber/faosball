@if ($alert)
    <div x-data="{ show: true }" x-show="show" x-transition class="alert {{ $alert['class'] }}">

        <div class="flex w-full items-center gap-3">

            <span class="alert-icon">

                @switch($alert['icon'])
                    @case('success')
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0Z" />
                        </svg>
                    @break

                    @case('error')
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414Z" />
                        </svg>
                    @break

                    @case('warning')
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.516 11.59C19.01 16.02 18.04 17.75 16.516 17.75H3.484c-1.524 0-2.494-1.73-1.742-3.061l6.515-11.59ZM10 7a1 1 0 00-1 1v3a1 1 0 002 0V8a1 1 0 00-1-1Zm0 7a1 1 0 100-2 1 1 0 000 2Z" />
                        </svg>
                    @break

                    @default
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm1-11H9v5h2V7Zm0 7H9v2h2v-2Z" />
                        </svg>
                @endswitch

            </span>


            <p class="alert-message">
                {{ $alert['message'] }}
            </p>


            <button type="button" class="alert-close ml-auto" @click="show=false">

                <svg width="16" height="16" viewBox="0 0 16 16">
                    <path d="M3 3L13 13M13 3L3 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>

            </button>

        </div>

    </div>
@endif
