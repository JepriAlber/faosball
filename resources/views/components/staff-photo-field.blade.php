<div class="form-group" x-data="{ imagePreview: @js($currentPhotoUrl) }">

    <label class="form-label">
        {{ __('Foto Staff') }}
    </label>

    <div class="form-file-upload">

        <input type="file" name="photo" accept="image/*"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
            @change="
            const file=$event.target.files[0];
            if(file){
                const reader=new FileReader();
                reader.onload=(e)=>imagePreview=e.target.result;
                reader.readAsDataURL(file);
            }
        ">

        <div x-show="!imagePreview" class="empty-state">

            <span class="avatar avatar-lg mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 16V8M8 12L12 8L16 12M3 15V18C3 18.5 3.2 19 3.6 19.4C4 19.8 4.5 20 5 20H19C19.5 20 20 19.8 20.4 19.4C20.8 19 21 18.5 21 18V15"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>

            <p class="empty-title">
                {{ $currentPhotoUrl ? __('Klik untuk mengganti foto staff') : __('Klik untuk upload foto staff') }}
            </p>

            <p class="empty-description">
                {{ __('JPG, PNG, WEBP maksimal 2MB') }}
            </p>

        </div>

        <div x-show="imagePreview" x-cloak class="flex flex-col items-center">
            <div class="avatar avatar-xl avatar-square mb-4">
                <img :src="imagePreview" class="h-full w-full object-cover">
            </div>

            <span class="link-primary text-xs font-semibold">
                {{ __('Ganti Foto') }}
            </span>
        </div>

    </div>

    @error('photo')
        <span class="form-error">{{ $message }}</span>
    @enderror

</div>
