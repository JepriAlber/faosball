<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academy\AcademyProfileFormRequest;
use App\Services\AcademyManagementService;
use App\Services\AcademyService;

class AcademyProfileController extends Controller
{
    protected AcademyManagementService $academyManagementService;
    protected AcademyService $academyService;

    public function __construct(
        AcademyManagementService $academyManagementService,
        AcademyService $academyService
    ) {
        $this->academyManagementService = $academyManagementService;
        $this->academyService = $academyService;
    }

    /**
     * Academy diambil dari $academyService->current() -- BUKAN route model
     * binding by ID. Halaman ini tidak pernah menerima ID dari request sama
     * sekali, supaya Owner tidak bisa mengedit academy lain lewat URL yang
     * dikarang. Lihat issue.md Bagian 4.8.
     *
     * Super Admin tidak punya "academy sendiri" (id_academy = null), jadi
     * current() akan null untuknya -- ditangani sebagai 404, bukan error 500.
     */
    public function edit()
    {
        $academy = $this->academyService->current();

        abort_if(! $academy, 404, 'Academy tidak ditemukan untuk akun ini.');

        return view('academy-profile.edit', [
            'title' => 'Profil Academy',
            'breadcrumb' => [
                ['label' => 'Profil Academy'],
            ],
            'academy' => $academy,
        ]);
    }

    public function update(AcademyProfileFormRequest $request)
    {
        $academy = $this->academyService->current();

        abort_if(! $academy, 404, 'Academy tidak ditemukan untuk akun ini.');

        try {

            $this->academyManagementService->updateProfile($academy, $request->validated());

            return redirect()
                ->route('academy.profile.edit')
                ->with('success', 'Profil academy berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui profil academy');
        }
    }
}
