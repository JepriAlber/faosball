@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: @js($title) }">
        @include('partials.breadcrumb')
    </div>
    <!-- Breadcrumb End -->

    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 lg:p-8">
        <div
            class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Informasi Profil Academy</h3>
                <p class="text-sm text-gray-500">Masukkan detail lengkap untuk mendaftarkan akademi baru.
                </p>
            </div>
            <div>
                <a href="{{ route('academy.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('academy.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Left Column -->
                <div class="flex flex-col gap-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="mb-2.5 block text-sm font-medium text-gray-800">
                            Nama Academy <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            placeholder="Masukkan nama akademi"
                            class="w-full rounded-xl border @error('name') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500"
                            required>
                        @error('name')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tagline -->
                    <div>
                        <label for="tagline" class="mb-2.5 block text-sm font-medium text-gray-800">
                            Tagline / Slogan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline') }}"
                            placeholder="Contoh: Maju Bersama Sepakbola"
                            class="w-full rounded-xl border @error('tagline') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500"
                            required>
                        @error('tagline')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="mb-2.5 block text-sm font-medium text-gray-800">
                            Nomor Telepon <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                            placeholder="Contoh: 08123456789"
                            class="w-full rounded-xl border @error('phone') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500"
                            required>
                        @error('phone')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="mb-2.5 block text-sm font-medium text-gray-800">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            placeholder="Contoh: info@akademi.com"
                            class="w-full rounded-xl border @error('email') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500"
                            required>
                        @error('email')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Status Toggle -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Status Aktif
                        </label>
                        <div x-data="{ switcherOn: {{ old('status', 'true') == 'true' || old('status') === '1' ? 'true' : 'false' }} }">
                            <label for="status-toggle" class="flex cursor-pointer select-none items-center">
                                <div class="relative">
                                    <input type="checkbox" id="status-toggle" name="status" value="1" class="sr-only"
                                        @change="switcherOn = !switcherOn" :checked="switcherOn">
                                    <div class="block h-8 w-14 rounded-full bg-gray-200 transition-colors"
                                        :class="switcherOn && '!bg-brand-500'"></div>
                                    <div class="absolute left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-white transition-transform"
                                        :class="switcherOn && 'translate-x-full'"></div>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-500"
                                    x-text="switcherOn ? 'Aktif' : 'Nonaktif'"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="flex flex-col gap-5">
                    <!-- Logo Upload (Alpine JS Preview) -->
                    <div x-data="{ imagePreview: null }">
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Logo Academy
                        </label>

                        <!-- Drag & Drop Container -->
                        <div
                            class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-transparent py-6 px-4 text-center cursor-pointer hover:bg-gray-50 transition">
                            <input type="file" id="logo" name="logo"
                                class="absolute inset-0 z-50 h-full w-full opacity-0 cursor-pointer" accept="image/*"
                                @change="
                                       const file = $event.target.files[0];
                                       if (file) {
                                           const reader = new FileReader();
                                           reader.onload = (e) => { imagePreview = e.target.result; };
                                           reader.readAsDataURL(file);
                                       } else {
                                           imagePreview = null;
                                       }
                                   ">

                            <!-- State 1: Uploading New File (No Preview) -->
                            <div x-show="!imagePreview" class="flex flex-col items-center justify-center">
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 border border-gray-100 text-gray-500 mb-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12 16V8M8 12L12 8L16 12M3 15V18C3 18.5304 3.21071 19.0391 3.58579 19.4142C3.96086 19.7893 4.46957 20 5 20H19C19.5304 20 20.0391 19.7893 20.4142 19.4142C20.7893 19.0391 21 18.5304 21 18V15"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <p class="text-sm font-medium text-gray-700">
                                    Klik untuk unggah atau seret berkas ke sini
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Format SVG, PNG, JPG, atau WEBP (Maksimal 2MB)
                                </p>
                            </div>

                            <!-- State 2: Show Image Preview -->
                            <div x-show="imagePreview" class="flex flex-col items-center justify-center w-full" x-cloak>
                                <div
                                    class="relative h-32 w-32 overflow-hidden rounded-xl bg-gray-50 border border-gray-100 mb-3">
                                    <img :src="imagePreview" alt="Logo Preview" class="h-full w-full object-cover">
                                </div>
                                <span class="text-xs text-brand-500 font-semibold underline">Ganti Gambar</span>
                            </div>
                        </div>

                        @error('logo')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="mb-2.5 block text-sm font-medium text-gray-800">
                            Alamat <span class="text-red-500">*</span>
                        </label>
                        <textarea id="address" name="address" rows="3" placeholder="Masukkan alamat lengkap akademi"
                            class="w-full rounded-xl border @error('address') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500"
                            required>{{ old('address') }}</textarea>
                        @error('address')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description"
                            class="mb-2.5 block text-sm font-medium text-gray-800">
                            Deskripsi
                        </label>
                        <textarea id="description" name="description" rows="3"
                            placeholder="Jelaskan secara singkat mengenai profil akademi Anda"
                            class="w-full rounded-xl border @error('description') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Button Section -->
            <div class="mt-8 flex items-center justify-end gap-4 border-t border-gray-100 pt-6">
                <button type="reset"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Reset
                </button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-600">
                    Simpan Academy
                </button>
            </div>
        </form>
    </div>
@endsection
