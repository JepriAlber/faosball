<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffPosition\StaffPositionFormRequest;
use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use App\Services\AcademyService;
use App\Services\StaffPositionService;
use Illuminate\Http\Request;

class StaffPositionController extends Controller
{
    protected StaffPositionService $staffPositionService;
    protected AcademyService $academyService;

    public function __construct(StaffPositionService $staffPositionService, AcademyService $academyService)
    {
        $this->staffPositionService = $staffPositionService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'sort']));

        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('staff-positions.index', [
            'title' => __('Staff Position'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Staff Position')],
            ],
            'staffPositions' => $this->staffPositionService->paginate($filters),
            'statusCounts' => $this->staffPositionService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            // Opsi dropdown filter Academy -- cuma dibutuhkan Super Admin,
            // yang melihat staff position lintas seluruh academy.
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    public function create()
    {
        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('staff-positions.create', [
            'title' => __('Tambah Staff Position'),
            'breadcrumb' => [
                ['label' => __('Staff Position'), 'url' => route('staff-positions.index')],
                ['label' => __('Tambah Staff Position')],
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            // Super Admin: dikelompokkan per academy (Role tenant-scoped per
            // academy) supaya tidak salah pilih role dari academy lain.
            // Owner biasa: cukup role academy-nya sendiri.
            'roles' => $isSuperAdmin
                ? Role::whereNotNull('id_academy')->with('academy')->orderBy('name')->get()->groupBy(fn ($role) => $role->academy->name)
                : Role::forCurrentAcademy()->orderBy('name')->get(),
        ]);
    }

    public function store(StaffPositionFormRequest $request)
    {
        try {

            $this->staffPositionService->create($request->validated());

            return redirect()
                ->route('staff-positions.index')
                ->with('success', __('Staff position berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan staff position'));
        }
    }

    public function edit(StaffPosition $staffPosition)
    {
        return view('staff-positions.edit', [
            'title' => __('Edit Staff Position'),
            'breadcrumb' => [
                ['label' => __('Staff Position'), 'url' => route('staff-positions.index')],
                ['label' => __('Edit Staff Position')],
            ],
            'staffPosition' => $staffPosition,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            // Academy record sudah pasti (edit, bukan create) -- cukup role
            // milik academy itu, tidak perlu grouping optgroup lagi.
            'roles' => Role::where('id_academy', $staffPosition->id_academy)->orderBy('name')->get(),
        ]);
    }

    public function update(StaffPositionFormRequest $request, StaffPosition $staffPosition)
    {
        try {

            $this->staffPositionService->update($staffPosition, $request->validated());

            return redirect()
                ->route('staff-positions.index')
                ->with('success', __('Staff position berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui staff position'));
        }
    }

    public function destroy(StaffPosition $staffPosition)
    {
        try {

            $this->staffPositionService->delete($staffPosition);

            return redirect()
                ->route('staff-positions.index')
                ->with('success', __('Staff position berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus staff position'), 'staff-positions.index');
        }
    }
}
