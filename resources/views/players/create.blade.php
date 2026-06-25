@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: @js($title) }">
        @include('partials.breadcrumb')
    </div>
    <!-- Breadcrumb End -->

    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 lg:p-8">

        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Informasi Profil Player</h3>
                <p class="text-sm text-gray-500">Masukkan detail lengkap untuk mendaftarkan pemain baru.</p>
            </div>

            <div>
                <a href="{{ route('players.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('players.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- ================= LEFT COLUMN ================= -->
                <div class="flex flex-col gap-5">

                    <!-- Name -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Nama Player <span class="text-red-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan nama player"
                            class="w-full rounded-xl border @error('name') border-red-500 @else border-gray-200 @enderror bg-transparent px-5 py-3 text-sm outline-none transition focus:border-brand-500"
                            required>

                        @error('name')
                            <span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Nickname -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Nickname</label>

                        <input type="text" name="nickname" value="{{ old('nickname') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm" placeholder="Nickname">
                    </div>

                    <!-- Birth Date -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Tanggal Lahir</label>

                        <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Gender</label>

                        <select name="gender" class="w-full rounded-xl border px-5 py-3 text-sm">

                            <option value="">Pilih</option>
                            <option value="male" @selected(old('gender') == 'male')>Male</option>
                            <option value="female" @selected(old('gender') == 'female')>Female</option>

                        </select>
                    </div>

                    <!-- Nationality -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Nationality</label>

                        <input type="text" name="nationality" value="{{ old('nationality', 'Indonesia') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm">
                    </div>

                    <!-- Height -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Tinggi (cm)</label>
                        <input type="number" name="height" value="{{ old('height') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm">
                    </div>

                    <!-- Weight -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Berat (kg)</label>
                        <input type="number" name="weight" value="{{ old('weight') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm">
                    </div>

                    <!-- Preferred Foot -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Kaki Dominan</label>

                        <select name="preferred_foot" class="w-full rounded-xl border px-5 py-3 text-sm">

                            <option value="">Pilih</option>
                            <option value="right" @selected(old('preferred_foot') == 'right')>Right</option>
                            <option value="left" @selected(old('preferred_foot') == 'left')>Left</option>
                            <option value="both" @selected(old('preferred_foot') == 'both')>Both</option>

                        </select>
                    </div>

                </div>

                <!-- ================= RIGHT COLUMN ================= -->
                <div class="flex flex-col gap-5">

                    <!-- Primary Position -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Posisi Utama <span class="text-red-500">*</span>
                        </label>

                        <input type="text" name="primary_position" value="{{ old('primary_position') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm" placeholder="Forward / Midfielder" required>
                    </div>

                    <!-- Secondary Position -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Posisi Kedua
                        </label>

                        <input type="text" name="secondary_position" value="{{ old('secondary_position') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm">
                    </div>

                    <!-- Join Date -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Join Date</label>

                        <input type="date" name="join_date" value="{{ old('join_date') }}"
                            class="w-full rounded-xl border px-5 py-3 text-sm">
                    </div>

                    <!-- Status Toggle -->
                    <div x-data="{ switcherOn: {{ old('status', 'active') === 'active' ? 'true' : 'false' }} }">

                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Status</label>

                        <input type="hidden" name="status" :value="switcherOn ? 'active' : 'inactive'">

                        <label class="flex cursor-pointer items-center">
                            <div class="relative">
                                <input type="checkbox" class="sr-only" @change="switcherOn = !switcherOn"
                                    :checked="switcherOn">

                                <div class="block h-8 w-14 rounded-full bg-gray-200 transition-colors"
                                    :class="switcherOn && '!bg-brand-500'"></div>

                                <div class="absolute left-1 top-1 h-6 w-6 rounded-full bg-white transition-transform"
                                    :class="switcherOn && 'translate-x-full'"></div>
                            </div>

                            <span class="ml-3 text-sm text-gray-500" x-text="switcherOn ? 'Active' : 'Inactive'"></span>
                        </label>
                    </div>

                    <!-- Photo Upload -->
                    <div x-data="{ imagePreview: null }">

                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Foto Player
                        </label>

                        <div
                            class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-6 px-4 text-center cursor-pointer">

                            <input type="file" name="photo" accept="image/*"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                @change="
                                    const file = $event.target.files[0];
                                    if(file){
                                        const reader = new FileReader();
                                        reader.onload = e => imagePreview = e.target.result;
                                        reader.readAsDataURL(file);
                                    }
                                ">

                            <template x-if="!imagePreview">
                                <p class="text-sm text-gray-500">
                                    Klik atau drag untuk upload foto
                                </p>
                            </template>

                            <template x-if="imagePreview">
                                <img :src="imagePreview" class="h-32 w-32 rounded-xl object-cover">
                            </template>

                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="mb-2.5 block text-sm font-medium text-gray-800">Notes</label>

                        <textarea name="notes" rows="3" class="w-full rounded-xl border px-5 py-3 text-sm">{{ old('notes') }}</textarea>
                    </div>

                    <!-- Create Account Toggle -->
                    <div x-data="{ switcherOn: false }">

                        <label class="mb-2.5 block text-sm font-medium text-gray-800">
                            Create Account
                        </label>

                        <input type="hidden" name="create_account" :value="switcherOn ? 1 : 0">

                        <label class="flex cursor-pointer items-center">
                            <div class="relative">
                                <input type="checkbox" class="sr-only" @change="switcherOn = !switcherOn"
                                    :checked="switcherOn">

                                <div class="block h-8 w-14 rounded-full bg-gray-200 transition-colors"
                                    :class="switcherOn && '!bg-brand-500'"></div>

                                <div class="absolute left-1 top-1 h-6 w-6 rounded-full bg-white transition-transform"
                                    :class="switcherOn && 'translate-x-full'"></div>
                            </div>

                            <span class="ml-3 text-sm text-gray-500" x-text="switcherOn ? 'Enabled' : 'Disabled'"></span>
                        </label>

                        <!-- Account Fields -->
                        <div x-show="switcherOn" x-transition class="mt-4 space-y-3">

                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email"
                                class="w-full rounded-xl border px-5 py-3 text-sm">

                            <input type="password" name="password" placeholder="Password"
                                class="w-full rounded-xl border px-5 py-3 text-sm">

                        </div>

                    </div>

                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-8 flex items-center justify-end gap-4 border-t border-gray-100 pt-6">

                <button type="reset"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Reset
                </button>

                <button type="submit"
                    class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                    Simpan Player
                </button>

            </div>

        </form>
    </div>
@endsection
