<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Player;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Tentukan nama folder module & kode entitas dari class model-nya.
     * Module baru yang butuh Document (mis. Payment) tinggal tambah 1
     * arm match baru di sini -- TIDAK perlu ubah bagian lain Service ini
     * (issue15.md Bagian 2b).
     */
    protected function resolveContext(Model $documentable): array
    {
        return match (get_class($documentable)) {
            Staff::class => ['module' => 'staff', 'code' => $documentable->staff_code],
            Player::class => ['module' => 'players', 'code' => $documentable->player_code],
            default => throw new \Exception(__('Module dokumen untuk entitas ini belum didukung.')),
        };
    }

    /**
     * Upload dokumen baru untuk 1 entitas (Staff/Player/dst). Disimpan di
     * disk 'local' (PRIVAT, bukan 'public') -- lihat issue15.md Bagian 2a
     * & Aturan Emas.
     */
    public function upload(Model $documentable, UploadedFile $file, string $type): Document
    {
        $context = $this->resolveContext($documentable);

        // Nama file fisik WAJIB pakai UUID, bukan nama asli client apa
        // adanya -- cegah path traversal & tabrakan nama (Aturan Emas).
        $safeOriginalName = str_replace(['/', '\\'], '-', $file->getClientOriginalName());
        $filename = Str::uuid() . '-' . $safeOriginalName;

        $directory = $documentable->academy->code . '/' . $context['module'] . '/' . $context['code'];

        $path = $file->storeAs($directory, $filename, 'local');

        return Document::create([
            'id_academy' => $documentable->id_academy,
            'documentable_type' => get_class($documentable),
            'documentable_id' => $documentable->getKey(),
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    public function delete(Document $document): bool
    {
        if (Storage::disk('local')->exists($document->path)) {
            Storage::disk('local')->delete($document->path);
        }

        return $document->delete();
    }
}
