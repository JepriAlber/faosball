<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Player;
use App\Models\Staff;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function storeForStaff(StoreDocumentRequest $request, Staff $staff)
    {
        try {

            $this->documentService->upload($staff, $request->file('file'), $request->validated('type'));

            return back()->with('success', __('Dokumen berhasil diunggah.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengunggah dokumen'));
        }
    }

    public function storeForPlayer(StoreDocumentRequest $request, Player $player)
    {
        try {

            $this->documentService->upload($player, $request->file('file'), $request->validated('type'));

            return back()->with('success', __('Dokumen berhasil diunggah.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengunggah dokumen'));
        }
    }

    /**
     * Flat route -- tidak digerbang middleware permission per-module
     * (beda dari storeForStaff/storeForPlayer di atas), WAJIB
     * authorize() manual lewat DocumentPolicy (Aturan Emas).
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        return Storage::disk('local')->response($document->path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        try {

            $this->documentService->delete($document);

            return back()->with('success', __('Dokumen berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus dokumen'));
        }
    }
}
