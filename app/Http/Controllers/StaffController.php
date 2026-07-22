<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Academy;
use App\Models\Staff;
use App\Services\AcademyService;
use App\Services\EmploymentTypeService;
use App\Services\StaffPositionService;
use App\Services\StaffService;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    protected StaffService $staffService;
    protected AcademyService $academyService;
    protected EmploymentTypeService $employmentTypeService;
    protected StaffPositionService $staffPositionService;

    public function __construct(
        StaffService $staffService,
        AcademyService $academyService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService
    ) {
        $this->staffService = $staffService;
        $this->academyService = $academyService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only([
            'search', 'status', 'id_academy', 'id_employment_type', 'id_staff_position', 'gender', 'sort',
        ]));

        $isSuperAdmin = $this->academyService->isSuperAdmin();
        $academyId = $isSuperAdmin ? null : $this->academyService->currentId();

        return view('staff.index', [
            'title' => __('Staff'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Staff')],
            ],
            'staff' => $this->staffService->paginate($filters),
            'statusCounts' => $this->staffService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            // Opsi dropdown filter Academy -- cuma dibutuhkan Super Admin,
            // yang melihat staff lintas seluruh academy. Pola sama RoleController.
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            'employmentTypeOptions' => $this->employmentTypeService->selectable($academyId),
            'staffPositionOptions' => $this->staffPositionService->selectable($academyId),
        ]);
    }

    public function create()
    {
        $isSuperAdmin = $this->academyService->isSuperAdmin();
        $academyId = $isSuperAdmin ? null : $this->academyService->currentId();

        return view('staff.create', [
            'title' => __('Tambah Staff'),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Tambah Staff')],
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            'employmentTypes' => $this->employmentTypeService->selectable($academyId),
            'staffPositions' => $this->staffPositionService->selectable($academyId),
            // Super Admin selalu lolos otorisasi apapun (Gate::before()), tapi
            // ditambahkan eksplisit di sini sebagai jaga-jaga untuk field
            // sensitif -- lihat issue12.md Tahap 15b.
            'canViewSalary' => $isSuperAdmin || auth()->user()->can('salary.view'),
        ]);
    }

    public function store(StoreStaffRequest $request)
    {
        try {

            $this->staffService->create($request->validated());

            return redirect()
                ->route('staff.index')
                ->with('success', __('Staff berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan staff'));
        }
    }

    public function show(Staff $staff)
    {
        $staff->load(['academy', 'user.roles', 'contracts.employmentType', 'contracts.position']);

        return view('staff.show', [
            'title' => $staff->full_name,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name],
            ],
            'staff' => $staff,
            'canViewSalary' => auth()->user()->can('viewSalary', $staff),
        ]);
    }

    public function edit(Staff $staff)
    {
        return view('staff.edit', [
            'title' => __('Edit Staff'),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Edit Staff')],
            ],
            'staff' => $staff->load('activeContract.employmentType', 'activeContract.position'),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'canViewSalary' => auth()->user()->can('viewSalary', $staff),
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        try {

            $this->staffService->update($staff, $request->validated());

            return redirect()
                ->route('staff.index')
                ->with('success', __('Staff berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui staff'));
        }
    }

    public function destroy(Staff $staff)
    {
        try {

            $this->staffService->delete($staff);

            return redirect()
                ->route('staff.index')
                ->with('success', __('Staff berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus staff'), 'staff.index');
        }
    }
}
