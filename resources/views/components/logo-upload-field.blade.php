@props([
    'currentLogoUrl' => null,
    'name' => 'logo',
    'label' => __('Logo Academy'),
    'helpText' => __('SVG, PNG, JPG, WEBP maksimal 2MB -- akan diminta crop persegi setelah dipilih'),
    'cropTitle' => __('Sesuaikan Logo'),
    'cropDescription' => __('Geser & perbesar untuk memilih area logo (persegi).'),
    'aspectRatio' => 1,
    'outputWidth' => 1024,
    'outputHeight' => 1024,
    'previewClass' => 'avatar avatar-lg avatar-square',
])

<div class="form-group"
    x-data="logoCropField('{{ $currentLogoUrl }}', {{ $aspectRatio }}, {{ $outputWidth }}, {{ $outputHeight }})">

    <label class="form-label">
        {{ $label }}
    </label>

    <div class="form-file-upload">

        <input type="file" id="{{ $name }}" name="{{ $name }}" x-ref="fileInput"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" accept="image/*"
            @change="onFileSelected($event)">

        <div x-show="!imagePreview" class="empty-state">

            <span class="avatar avatar-lg mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 16V8M8 12L12 8L16 12M3 15V18C3 18.5 3.2 19 3.6 19.4C4 19.8 4.5 20 5 20H19C19.5 20 20 19.8 20.4 19.4C20.8 19 21 18.5 21 18V15"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>

            <p class="empty-title">
                {{ __('Klik untuk unggah logo') }}
            </p>

            <p class="empty-description">
                {{ $helpText }}
            </p>

        </div>

        <div x-show="imagePreview" x-cloak class="flex flex-col items-center">

            <div class="{{ $previewClass }} mb-3">
                <img :src="imagePreview" class="h-full w-full object-cover">
            </div>

            <span class="link-primary text-xs font-semibold">
                {{ __('Ganti gambar') }}
            </span>

        </div>

    </div>

    @error($name)
        <span class="form-error">{{ $message }}</span>
    @enderror

    {{-- Modal Crop --}}
    <div x-show="showCropModal" x-cloak x-transition
        class="modal-overlay flex items-center justify-center p-4">

        <div class="modal-container modal-md">

            <div class="modal-header">
                <div>
                    <h3 class="modal-title">{{ $cropTitle }}</h3>
                    <p class="modal-description">{{ $cropDescription }}</p>
                </div>
            </div>

            <div class="modal-body">
                <div class="h-[400px] w-full overflow-hidden">
                    <template x-if="pendingSourceUrl">
                        <img :src="pendingSourceUrl" x-ref="cropperImage" class="block max-w-full">
                    </template>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="cancelCrop()">
                    {{ __('Batal') }}
                </button>

                <button type="button" class="btn btn-primary" @click="confirmCrop()">
                    {{ __('Pakai Crop Ini') }}
                </button>
            </div>

        </div>

    </div>

</div>
