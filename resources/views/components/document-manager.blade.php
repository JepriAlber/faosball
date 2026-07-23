{{--
    Reusable document upload + list, dipakai lintas module (Staff, Player,
    nanti Payment). Props:
    - documentable : model target (Staff/Player/dst), harus punya
                      relasi documents() (MorphMany).
    - upload-route  : URL tujuan POST form upload (sudah termasuk parameter
                       parent-nya, mis. route('staff.documents.store', $staff)).
    - types         : array [key => label] dari config('faos.document_types.<module>').
    - can-manage    : boolean, kontrol tampil/sembunyi form upload & tombol hapus
                       (otorisasi SESUNGGUHNYA tetap di route/Policy, ini cuma UX).
--}}
<div class="space-y-4">

    @if ($canManage)
        <form action="{{ $uploadRoute }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800 sm:flex-row sm:items-end">
            @csrf

            <div class="form-group mb-0 flex-1">
                <label class="form-label">{{ __('Jenis Dokumen') }}</label>
                <select name="type" class="form-select @error('type') form-danger @enderror" required>
                    <option value="">{{ __('Pilih Jenis Dokumen') }}</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group mb-0 flex-1">
                <label class="form-label">{{ __('Berkas') }}</label>
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="form-input @error('file') form-danger @enderror" required>
                @error('file')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary shrink-0">{{ __('Unggah') }}</button>
        </form>
    @endif

    <div class="space-y-2">
        @forelse ($documents as $document)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                <div class="min-w-0">
                    <span class="table-title truncate">{{ $document->original_name }}</span>
                    <span class="table-subtitle">
                        {{ $types[$document->type] ?? $document->type }}
                        &middot; {{ round($document->size / 1024, 1) }} KB
                        &middot; {{ $document->created_at->format('d M Y') }}
                    </span>
                </div>

                <div class="flex shrink-0 gap-2">
                    <a href="{{ route('documents.show', $document) }}" target="_blank" class="btn-icon" title="{{ __('Lihat') }}">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                            <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </a>

                    @if ($canManage)
                        <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('{{ __('Hapus dokumen ini?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Hapus') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M2.5 5H17.5M6.66667 5V3.33333C6.66667 2.8731 7.03976 2.5 7.5 2.5H12.5C12.9602 2.5 13.3333 2.8731 13.3333 3.33333V5M15.8333 5V15.8333C15.8333 16.2936 15.4602 16.6667 15 16.6667H5C4.53976 16.6667 4.16667 16.2936 4.16667 15.8333V5H15.8333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen yang diunggah.') }}</p>
        @endforelse
    </div>

</div>
