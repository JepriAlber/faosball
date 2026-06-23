@extends('layouts.app', ['page' => 'academy'])

@section('title', $academy->name . ' - ' . config('app.name'))

@section('content')
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: @js($title) }">
        @include('partials.breadcrumb')
    </div>
    <!-- Breadcrumb End -->

    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 lg:p-8">
        <!-- Header Section -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-5">
            <div class="flex items-center gap-4">
                <div
                    class="h-16 w-16 overflow-hidden rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0">
                    @if ($academy->logo)
                        <img src="{{ asset('storage/' . $academy->logo) }}" alt="Logo {{ $academy->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="font-bold text-gray-400 text-2xl">
                            {{ strtoupper(substr($academy->name, 0, 2)) }}
                        </span>
                    @endif
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-800">{{ $academy->name }}</h3>
                    <p class="text-sm text-gray-500 italic">"{{ $academy->tagline }}"</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('academy.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Kembali
                </a>
                <a href="{{ route('academy.edit', $academy->id_academy) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-600">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Ubah Profile
                </a>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Info Panel (General Details) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description -->
                <div class="rounded-xl border border-gray-100 p-5">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Deskripsi Academy</h4>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                        {{ $academy->description ?: 'Tidak ada deskripsi profil untuk akademi ini.' }}
                    </p>
                </div>

                <!-- Address -->
                <div class="rounded-xl border border-gray-100 p-5">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Alamat Lengkap</h4>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                        {{ $academy->address }}
                    </p>
                </div>
            </div>

            <!-- Right Info Panel (Quick Attributes) -->
            <div class="space-y-6">
                <!-- Status & Contacts -->
                <div class="rounded-xl border border-gray-100 p-5 space-y-4">
                    <h4
                        class="text-sm font-semibold text-gray-800 border-b border-gray-100 pb-2">
                        Informasi Ringkas</h4>

                    <!-- Status -->
                    <div>
                        <span class="block text-xs text-gray-400 mb-1">Status Keaktifan</span>
                        @if ($academy->status)
                            <span
                                class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                Aktif
                            </span>
                        @else
                            <span
                                class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">
                                Nonaktif
                            </span>
                        @endif
                    </div>

                    <!-- Email -->
                    <div>
                        <span class="block text-xs text-gray-400 mb-1">Email Resmi</span>
                        <a href="mailto:{{ $academy->email }}"
                            class="text-sm font-medium text-brand-500 hover:underline break-all">
                            {{ $academy->email }}
                        </a>
                    </div>

                    <!-- Phone -->
                    <div>
                        <span class="block text-xs text-gray-400 mb-1">Nomor Telepon</span>
                        <span class="text-sm font-medium text-gray-800">
                            {{ $academy->phone }}
                        </span>
                    </div>

                    <!-- Slug -->
                    <div>
                        <span class="block text-xs text-gray-400 mb-1">Slug URL</span>
                        <span
                            class="text-sm font-medium text-gray-600 break-all bg-gray-50 px-2 py-0.5 rounded text-xs font-mono">
                            {{ $academy->slug }}
                        </span>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="rounded-xl border border-gray-100 p-5 space-y-3 text-xs text-gray-400">
                    <div class="flex justify-between">
                        <span>Dibuat pada:</span>
                        <span
                            class="font-medium text-gray-600">{{ $academy->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pembaruan terakhir:</span>
                        <span
                            class="font-medium text-gray-600">{{ $academy->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
